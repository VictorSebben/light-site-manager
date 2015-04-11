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




