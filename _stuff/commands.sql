CREATE ROLE ecommaster
    WITH LOGIN PASSWORD '1234' REPLICATION
    VALID UNTIL 'infinity';

CREATE DATABASE ecommaster
  WITH OWNER=ecommaster
       ENCODING='UTF-8'
       LC_COLLATE='en_US.UTF-8'
       LC_CTYPE='en_US.UTF-8'
       TEMPLATE=template1
       CONNECTION LIMIT=-1;

-- Connect to the newly created database!!! Wow! Amazing.
\c ecommaster;

CREATE TABLE cat_users (
    id SERIAL NOT NULL PRIMARY KEY,
    description VARCHAR( 32 )
);

INSERT INTO cat_users ( description ) VALUES
( 'admin' ),
( 'editor' ),
( 'blogger' );

CREATE TABLE users (
    id SERIAL NOT NULL PRIMARY KEY,
    name VARCHAR( 64 ) NOT NULL,
    email VARCHAR( 64 ) NOT NULL,
    password CHAR( 60 ) NOT NULL,
    cat_id INTEGER NOT NULL,
    FOREIGN KEY ( cat_id ) REFERENCES cat_users ( id )
);

ALTER TABLE users ADD COLUMN status SMALLINT DEFAULT 1;

INSERT INTO users (name, email, password, cat_id) VALUES
( 'Yoda', 'yoda@jedi.net', '1234', 1 ),
( 'Luke', 'luke.theforce.net', 4321, 2 ),
( 'Vader', 'vader@darkside.net', 'asdf', 1 );

CREATE ROLE ecommaster WITH
    LOGIN PASSWORD '1234'
    CREATEDB REPLICATION
    VALID UNTIL 'infinity';

-- populate users table with mocked users
INSERT INTO users (name, email, password, cat_id)
VALUES ('user1', 'user1@foo.com', '1234', 1),
  ('user2', 'user2@foo.com', '1234', 1),
  ('user3', 'user3@foo.com', '1234', 1),
  ('user4', 'user4@foo.com', '1234', 1),
  ('user5', 'user5@foo.com', '1234', 1),
  ('user6', 'user6@foo.com', '1234', 1),
  ('user7', 'user7@foo.com', '1234', 1),
  ('user8', 'user8@foo.com', '1234', 1);

ALTER TABLE users ALTER COLUMN password TYPE VARCHAR(255);

ALTER TABLE users ADD COLUMN created_at TIMESTAMP WITHOUT TIME ZONE;
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP WITHOUT TIME ZONE;

-- TODO see if this is going to be used
CREATE TABLE users_sessions (
  id SERIAL NOT NULL PRIMARY KEY,
  user_id INT NOT NULL,
  hash VARCHAR(50) NOT NULL
);

ALTER TABLE users_sessions
ADD CONSTRAINT users_session_user_fkey FOREIGN KEY (user_id)
REFERENCES users (id) ON DELETE CASCADE;


-- Authentication tables
CREATE TABLE roles (
  id   SERIAL      NOT NULL PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);


CREATE TABLE permissions (
  id          SERIAL      NOT NULL PRIMARY KEY,
  description VARCHAR(50) NOT NULL
);
ALTER TABLE permissions ADD CONSTRAINT permissions_description_unique UNIQUE (description);

CREATE TABLE role_perm (
  role_id INTEGER NOT NULL,
  perm_id INTEGER NOT NULL,

  FOREIGN KEY (role_id) REFERENCES roles (id),
  FOREIGN KEY (perm_id) REFERENCES permissions (id)
);

CREATE TABLE user_role (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,

  FOREIGN KEY (user_id) REFERENCES users (id),
  FOREIGN KEY (role_id) REFERENCES roles (id)
);

-- Adjust auth tables
ALTER TABLE users DROP COLUMN cat_id;
DROP TABLE cat_users;

ALTER TABLE roles ADD COLUMN created_at TIMESTAMP WITHOUT TIME ZONE;
ALTER TABLE roles ADD COLUMN updated_at TIMESTAMP WITHOUT TIME ZONE;

ALTER TABLE permissions ADD COLUMN created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT now();
ALTER TABLE permissions ADD COLUMN updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT now();

INSERT INTO roles (name) VALUES ('admin'), ('editor');

INSERT INTO permissions (description) VALUES ('sysadm_access'), ('edit_other_users');

INSERT INTO role_perm (role_id, perm_id) VALUES (1, 1), (1, 2);
