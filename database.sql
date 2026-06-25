CREATE TABLE IF NOT EXISTS urls (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name varchar(255) NOT NULL UNIQUE,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS url_checks (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    url_id bigint NOT NULL REFERENCES urls(id) ON DELETE CASCADE,
    status_code int,
    h1 varchar(255),
    title varchar(255),
    description text,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);