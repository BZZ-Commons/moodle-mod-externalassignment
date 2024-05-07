import {call as fetchMany} from 'core/ajax';

export const fetchAllStudents = (
    courseid
) => fetchMany([{
    methodname: 'mod_externalassignment_read_students',
    args: {
        courseid
    },
}])[0];