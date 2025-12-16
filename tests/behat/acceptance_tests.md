# List of acceptance tests for mod externalassignment

## 1) Create new external assignment

| # | Due Date | Cut Off Date | Completion | Feature                          |
|---|----------|--------------|------------|----------------------------------|
| a | Y        | Y            | pass       | cutoff_and_passingggrade.feature |
| b | Y        | Y            | manual     | cutoff_and_manual.feature        |
| c | Y        | Y            | none       | cutoff_no_conditions.feature     |
| d | Y        | N            | pass       | due_and_passinggrade.feature     |
| e | Y        | N            | manual     | due_and_manual.feature           |
| f | Y        | N            | none       | due_no_conditions.feature        |
| g | N        | N            | pass       | nodates_and_passinggrade.feature |
| h | N        | N            | manual     | nodates_and_manual.feature       |
| i | N        | N            | none       | nodates_no_conditions.features   |

## 2) Errors while creating external assignment

| # | Condition                         | Feature |
|---|-----------------------------------|---------|
| a | Open date >= due date             | -       |
| b | Due date >= cut off date          | -       |
| c | Link to external site missing     | -       |
| d | Link to external site invalid URL | -       |

## 3) Edit existing external assignment

| a | | - |

## 4) Manual grading

| # | Existing grade | Completion | Before | After | Feature |
|---|----------------|------------|--------|-------|---------|
| a | N              | none       | -      | -     | -       |
| b | N              | manual     | -      | -     | -       |
| c | N              | pass       | -      | N     | -       |
| d | N              | pass       | -      | Y     | -       |
| e | Y              | none       | -      | -     | -       |
| f | Y              | manual     | Y      | Y     | -       |
| g | Y              | manual     | N      | N     | -       |
| h | Y              | pass       | Y      | Y     | -       |
| i | Y              | pass       | Y      | N     | -       |   
| j | Y              | pass       | N      | Y     | -       |
| k | Y              | pass       | N      | N     | -       |