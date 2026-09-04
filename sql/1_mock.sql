-- wow would you look at that

-- Users

INSERT INTO users (
    username,
    password_hash,
    is_admin
)
VALUES
    (
        'admin',
        'JDJ5JDEwJFgyQm5IUnpCRTRrTUNybzBVV2V2Sy5rT0VpdkZDbmVWUnJ3VUV5cmpQUWk5TkFYNWtscmMy',
        TRUE
    ),
    (
        'jeffery_williams',
        'JDJ5JDEwJEY4LnA4bmkvTkdvY000eFc1Y2ovVi5NSDQvbTU5M1lPeW1LQ1hzR3ZmaDNaMzJFaENpQWN5',
        FALSE
    ),
    (
        'linus_torvalds',
        'JDJ5JDEwJDU4TVNPVUd6Zjd1ZmxMOXlMbkc3Sy5wcjB1MHJKc2NXVnpFUjAzakcwLlM0ZlMuRkEyMkJT',
        FALSE
    ),
    (
        'jordanCarter',
        'JDJ5JDEwJG1pRk0yOVJTUmdmZnpJeTVXc0FCeU9ObW5RSUlnZ093MFJ6VEdLVFZ0bnBjUGQzTGpZdEhh',
        FALSE
    ),
    (
        'richSTALLMAN',
        'JDJ5JDEwJDBINE9pVm5hOEExbXZ1VnQ3MFZaU2UwbG5UeXJXamtuUS4vOXl0MmtFLlVFR3FIeERCM1VP',
        FALSE
    ),
    (
        'lo',
        'JDJ5JDEwJGx3cUFwTG01ZTUyOHJocDJRTmYzc095NWRJZktkMTkxWEpEdVNvWkZxekp2N01maFE5M2Z5',
        TRUE
    );


-- Tasks

INSERT INTO tasks (
    title,
    "desc",
    priority,
    deadline,
    created_at,
    status
)
VALUES
    (
        'Move the old data over',
        'Move the data from the old database into the new schema',
        15,
        NOW() + INTERVAL '7 days',
        NOW() - INTERVAL '3 days',
        'ONGOING'
    ),
    (
        'Finish the API docs',
        'Document the public endpoints and add examples for each one',
        5,
        NOW() + INTERVAL '3 days',
        NOW() - INTERVAL '1 day',
        'SUBMITTED'
    ),
    (
        'Clean up the dashboard',
        'Give the dashboard a cleaner layout and update the overall look',
        10,
        NOW() + INTERVAL '14 days',
        NOW() - INTERVAL '5 days',
        'FINISHED'
    ),
    (
        'Get CI/CD working',
        'Set up GitHub Actions to build, test, and deploy the app automatically',
        18,
        NOW() + INTERVAL '2 days',
        NOW() - INTERVAL '12 hours',
        'ONGOING'
    ),
    (
        'Go through the security issues',
        'Check the project for known vulnerabilities and update anything that needs it',
        20,
        NOW() + INTERVAL '5 days',
        NOW() - INTERVAL '2 days',
        'SUBMITTED'
    ),
    (
        'Fix the login timeout',
        'Fix the session expiring too quickly on mobile browsers',
        8,
        NOW() + INTERVAL '1 day',
        NOW() - INTERVAL '6 hours',
        'ONGOING'
    ),
    (
        'Speed up the task queries',
        'Find the slow queries and add the indexes we are missing',
        12,
        NOW() - INTERVAL '1 day',
        NOW() - INTERVAL '4 days',
        'FINISHED'
    ),
    (
        'Plan the penetration test',
        'Work with an external security team to test the app before the compliance review',
        19,
        NOW() + INTERVAL '90 days',
        NOW() - INTERVAL '30 days',
        'SUBMITTED'
    ),
    (
        'Move the app to Kubernetes',
        'Move the current deployment to a Kubernetes cluster',
        14,
        NOW() + INTERVAL '60 days',
        NOW() - INTERVAL '15 days',
        'SUBMITTED'
    ),
    (
        'Get ready for the GDPR audit',
        'Review our data retention rules and make sure users can export their data',
        16,
        NOW() + INTERVAL '120 days',
        NOW() - INTERVAL '45 days',
        'SUBMITTED'
    );


-- Categories

INSERT INTO categories (title)
VALUES
    ('Backend'),
    ('Frontend'),
    ('DevOps'),
    ('Security'),
    ('QA & Testing'),
    ('Infrastructure'),
    ('Mobile'),
    ('Compliance');


-- Comments

INSERT INTO comments (
    body,
    submission_time,
    user_id,
    task_id
)
VALUES
    (
        'I started going through the old tables. A few of them need some cleanup first.',
        NOW() - INTERVAL '1 day',
        2,
        1
    ),
    (
        'The docs are mostly done. I just need to add examples for a couple of endpoints.',
        NOW() - INTERVAL '2 hours',
        3,
        2
    ),
    (
        'The pipeline is getting stuck during the tests. Looks like some environment variables are missing.',
        NOW() - INTERVAL '5 hours',
        5,
        4
    ),
    (
        'Found a few high severity issues in the npm dependencies. I am working through them now.',
        NOW() - INTERVAL '3 hours',
        4,
        5
    ),
    (
        'The fix is deployed to staging. Just waiting for QA to give it a once-over.',
        NOW() - INTERVAL '30 minutes',
        1,
        6
    ),
    (
        'Added the missing indexes and the queries are noticeably faster now.',
        NOW() - INTERVAL '1 day',
        2,
        7
    ),
    (
        'Started putting together the audit scope so we are not scrambling when the time comes.',
        NOW() - INTERVAL '20 days',
        6,
        8
    );


-- Subtasks

INSERT INTO sub_tasks (
    title,
    is_done,
    task_id
)
VALUES
    (
        'Export the old database data',
        TRUE,
        1
    ),
    (
        'Import the data into PostgreSQL',
        FALSE,
        1
    ),
    (
        'Sketch out the new dashboard',
        TRUE,
        3
    ),
    (
        'Write the GitHub Actions workflow',
        TRUE,
        4
    ),
    (
        'Set up the production secrets',
        FALSE,
        4
    ),
    (
        'Run a security scan',
        TRUE,
        5
    ),
    (
        'Update the outdated packages',
        FALSE,
        5
    ),
    (
        'Reproduce the bug on Safari',
        TRUE,
        6
    ),
    (
        'Apply the fix and push it',
        TRUE,
        6
    ),
    (
        'Pick an external security firm',
        FALSE,
        8
    ),
    (
        'Set up the staging Kubernetes nodes',
        FALSE,
        9
    );


-- Task assignments

INSERT INTO user_tasks (
    user_id,
    task_id
)
VALUES
    (2, 1),
    (3, 2),
    (2, 3),
    (3, 3),
    (5, 4),
    (4, 5),
    (1, 6),
    (2, 7),
    (4, 7),
    (6, 8),
    (5, 9),
    (1, 10);


-- Task categories

INSERT INTO task_categories (
    task_id,
    category_id
)
VALUES
    (1, 1),
    (1, 3),
    (2, 1),
    (3, 2),
    (4, 3),
    (4, 6),
    (5, 4),
    (6, 2),
    (6, 7),
    (7, 1),
    (8, 4),
    (9, 3),
    (9, 6),
    (10, 8);
