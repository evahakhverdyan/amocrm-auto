<?php

namespace AMO;


class AutoSchoolLead
{
    protected $rawData;

    protected $paymentFields = [413511, 413515, 413517, 413519, 571769];
    protected $invoiceFields = [539217, 539221, 539223, 539225, 571771];
    protected $sidePaymentFields = [587233, 561445];

    public static function createFromArray($rawData) {
        return new self($rawData);
    }

    public function __construct($rawData) {
        $this->rawData = $rawData;
    }

    public function findCustomField($fieldId) {
        foreach ($this->rawData['custom_fields'] as $fieldData) {
            if ($fieldData['id'] == $fieldId) {
                return $fieldData;
            }
        }

        if ($this->rawData['_extra']) {
            foreach ($this->rawData['_extra']['custom_fields'] as $fieldData) {
                if ($fieldData['id'] == $fieldId) {
                    return $fieldData;
                }
            }
        }

        return null;
    }
    public function fieldExists($fieldId) {
        $field = $this->findCustomField($fieldId);
        return !empty($field);
    }
    public function getCustomFieldValue($fieldId) {
        if (isset($this->rawData['cf' . $fieldId])) {
            return $this->rawData['cf' . $fieldId];
        }

        $customField = $this->findCustomField($fieldId);
        if (!$customField) {
            return null;
        }

        $fieldValue = $customField['values'][0]['value'];

        if ($fieldValue === "false") {
            $fieldValue = false;
        }

        return $fieldValue;
    }

    public function getCustomFieldName($fieldId) {
        $field = $this->findCustomField($fieldId);

        if (!$field) {
            return null;
        }

        return $field['name'];
    }

    public function getPaymentValue($fieldId) {
        $fieldValue = $this->getCustomFieldValue($fieldId);

        preg_match('#^[\d \.,]+#', $fieldValue, $matches);
        if ($matches[0]) {
            $preparedValue = preg_replace('#\W#', '', $matches[0]);
            return intval($preparedValue);
        }

        return false;
    }

    public function getPaymentDate($fieldId) {
        return $this->getDateFromValue( $this->getCustomFieldValue($fieldId) );
    }

    public function isEverythingPayed() {
        return $this->getCustomFieldValue(583197) === '1';
    }

    public function totalDebt() {
        $studyPrice = $this->studyPrice();

        if (!$studyPrice) {
            return $this->getPaymentValue(552815);
        }

        $debt = $studyPrice - $this->totalPaymentsMade();
        return $debt > 0 ? $debt : 0;
    }

    public function studyPrice() {
        $payment = $this->getPaymentValue(587713);
        if ($payment > 0) {
            return $payment;
        }

        return false;
    }

    public function totalPaymentsMade() {
        $sum = 0;

        foreach ($this->sidePaymentFields as $fieldId) {
            $sum += $this->getPaymentValue($fieldId);
        }

        foreach ($this->paymentFields as $fieldId) {
            $sum += $this->getPaymentValue($fieldId);
        }

        return $sum;
    }

    public function phone() {
        $phone = $this->getCustomFieldValue(389479);
        if ( is_array($phone) ) {
            $phone = $phone[0];
        }

        $phone = preg_replace('#\W#', '', $phone);
        if ($phone[0] === '8') {
            $phone[0] = '7';
        }

        if ($phone[0] !== '7') {
            $phone = '7'.$phone;
        }

        return '+'.$phone;
    }

    public function group() {
        return $this->getCustomFieldValue(580073);
    }

    public function sidePayments() {
        $sidePayments = [
            "Остаток" => $this->totalDebt(),
        ];

        foreach ($this->sidePaymentFields as $fieldId) {
            if ($this->fieldExists($fieldId)) {
                $sidePayments[$this->getCustomFieldName($fieldId)] = $this->getPaymentValue($fieldId);
            }
        }

        return $sidePayments;
    }

    public function paymentDetails() {
        $details = $this->sidePayments();

        foreach ($this->paymentFields as $index => $fieldId) {
            if ($this->fieldExists($fieldId)) {
                $fieldName = $this->getCustomFieldName($fieldId);
                $paymentValue = $this->getPaymentValue($fieldId);
                $hasPayment = $paymentValue !== "не задано" && $paymentValue !== "нет";

                if ($hasPayment) {
                    $details[$fieldName] = $paymentValue;
                }
            }
        }

        return $details;
    }

    private function getDateFromValue($valueWithDate) {
        if (!$valueWithDate) {
            return false;
        }

        preg_match('#\d{2}.\d{2}.\d{4}#', $valueWithDate, $matches);
        if ($matches[0]) {
            $ddmmYY = $matches[0];
            $parsedDate = \DateTime::createFromFormat('d.m.Y', $ddmmYY);
            return $parsedDate;
        }

        return false;
    }

    function getPaymentOverdueDays() {
        if ($this->isEverythingPayed()) {
            return 0;
        }

        if ($this->totalDebt() === 0) {
            return 0;
        }

        $lastSidePaymentDate = false;
        foreach ($this->sidePaymentFields as $fieldId) {
            $paymentDate = $this->getPaymentDate($fieldId);

            if ($paymentDate) {
                if (!$lastSidePaymentDate) {
                    $lastSidePaymentDate = $paymentDate;
                }
                else {
                    $isNewDateGreater = $paymentDate->diff($lastSidePaymentDate) > 0;
                    if ($isNewDateGreater) {
                        $lastSidePaymentDate = $paymentDate;
                    }
                }
            }
        }

        $allInvoicesPayed = true;
        $unpayedInvoiceDate = false;

        foreach ($this->paymentFields as $index => $paymentFieldId) {
            $paymentValue = $this->getPaymentValue($paymentFieldId);

            $invoiceFieldId = $this->invoiceFields[$index];
            $invoiceDate = $this->getPaymentDate($invoiceFieldId);

            if ($invoiceDate) {
                $isInvoicePayed = $paymentValue > 0;
                $allInvoicesPayed = $allInvoicesPayed && $isInvoicePayed;

                if (!$isInvoicePayed && !$unpayedInvoiceDate) {
                    $unpayedInvoiceDate = $invoiceDate;
                }
            }
        }

        $lastPaymentDate = $lastSidePaymentDate;
        if ($unpayedInvoiceDate) {
            if ($lastPaymentDate) {
                $isInvoceDateGreater = $unpayedInvoiceDate->diff($lastPaymentDate) > 0;
                if ($isInvoceDateGreater) {
                    $lastPaymentDate = $unpayedInvoiceDate;
                }
            }
            else {
                $lastPaymentDate = $unpayedInvoiceDate;
            }
        }

        if (!$lastPaymentDate) {
            return 0;
        }

        $today = new \DateTime();
        $daysFromLastPayment = $today->diff($lastPaymentDate)->days;
        return $daysFromLastPayment;
    }
}