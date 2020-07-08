<template>
    <section class="tab-content px-3 pt-4">
        <div class="tab-pane fade show active">
            <h2 class="text-center">Инструкторы</h2>

            <b-dropdown :text="currentInstructor ? currentInstructor.name : 'Выбор инструктора'" variant="primary" block>
                <b-dropdown-item v-for="instructor in instructors"
                        @click="updateCurrentInstructorId(instructor.id)"
                        :key="instructor.id"
                >{{instructor.name}}</b-dropdown-item>
            </b-dropdown>

             <div class="centralContainer mt-4">
                 <div v-if="currentInstructor" role="tablist">
                     <div>
                         Всего ЗП: {{currentInstructorSalary}}
                     </div>
                     <b-card no-body class="mb-2" v-for="(group, groupName) in currentInstructor.groups" :key="groupName">
                         <b-card-header header-tag="header" class="p-1" role="tab">
                             <b-button block v-b-toggle="'collapse'+groupCode(groupName)" variant="link" class="d-flex align-items-center">
                                 <span class="w-75 mr-4">Группа {{group.name}}</span><span class="btn btn-secondary w-25">{{group.salary}}</span>
                             </b-button>
                         </b-card-header>
                         <b-collapse :id="'collapse'+groupCode(groupName)" :accordion="'instructorAccordion'+currentInstructor.id" role="tabpanel">
                             <b-card-body>
                                 <ul class="list-group list-group-flush">
                                     <li class="list-group-item" v-for="student in currentInstructor.students[groupName]" :key="student.name">{{student.name}}</li>
                                 </ul>
                             </b-card-body>
                         </b-collapse>
                     </b-card>
                 </div>
                <div v-else>
                    Инструктор не выбран
                </div>
            </div>

        </div>
    </section>
</template>

<script>
    export default {
        name: "Instructors",
        props: ['groups', 'templates', 'instructors'],
        data() {
            return {
                currentInstructorId: false,
                groupNames: []
            }
        },
        methods: {
            updateCurrentInstructorId(newInstructorId) {
                this.currentInstructorId = newInstructorId;
            },
            groupCode(groupName) {
                let groupIndex = this.groupNames.indexOf(groupName);

                if (groupIndex !== -1) {
                    return groupIndex;
                }
                else {
                    this.groupNames.push(groupName);
                    return this.groupNames.indexOf(groupName);
                }
            }
        },
        computed: {
            currentInstructor() {
                if (!this.currentInstructorId ) {
                    return false;
                }

                return this.instructors.find(instructor => instructor.id === this.currentInstructorId) || false;
            },
            currentInstructorSalary() {
                if (!this.currentInstructorId ) {
                    return false;
                }

                return Object.keys(this.currentInstructor.groups).reduce( (summ, code) => {
                    let group = this.currentInstructor.groups[code];
                    summ += group.salary;
                    return summ;
                }, 0);
            }
        }

    }
</script>

<style scoped>

</style>