Action entity: (resource_id,action_description, action_type, action_state, user_id)


resource_id: BIGINT
action_description: VARCHAR
resource_type: NOT NULL
action_state: enum(SUCCESS, FAILURE, NA) NOT NULL
user_id: BIGINT NOT NULL FK




