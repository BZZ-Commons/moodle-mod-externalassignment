import {call as fetchMany} from 'core/ajax';

export const fetchAllStudents = (
    coursemoduleid,
    sort,
    tdir,
    status
) => fetchMany([{
    methodname: 'mod_externalassignment_read_students',
    args: {
        coursemoduleid,
        sort,
        tdir,
        status
    },
}])[0];