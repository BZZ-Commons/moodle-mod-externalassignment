# List of acceptance tests for mod externalassignment
## 1) Create new external assignment
| # | Due Date | Cut Off Date | Completion | Feature |
|---|----------|--------------|------------|---------|
| a | Y | Y | pass | — |
| b | Y | Y | manual | — |
| c | Y | Y | none | — |
| d | Y | N | pass | — |
| e | Y | N | manual | — |
| f | Y | N | none | — |
| g | N | N | pass | — |
| h | N | N | manual | — |
| i | N | N | none | — |

## 2) Errors while creating external assignment
| # | Condition | Feature |
|---|-----------|---------|
| a | Open date >= due date | - |
| b | Due date >= cut off date | - |
| c | Link to external site missing  | - |
| d |  Link to external site invalid URL | - |

## 3) Edit existing external assignment
| a |  | - |

## 4) Manual grading
| # | Existing grade | Completion | Before | After | Feature |
|---|----------------|------------|--------|-------|---------|
| a | N              | none       | -      | -     | - |
| b | N              | manual     | -      | -     | - |
| c | N              | pass       | -      | N     | - |
| d | N              | pass       | -      | Y     | - |
| e | Y              | none       | -      | -     | - |
| f | Y              | manual     | Y      | Y     | - |
| g | Y              | manual     | N      | N     | - |
| h | Y              | pass       | Y      | Y     | - |
| i | Y              | pass       | Y      | N     | - |   
| j | Y              | pass       | N      | Y     | - |
| k | Y              | pass       | N      | N     | - |