CREATE DATABASE ecommaster
  WITH OWNER=ecommaster
       ENCODING='UTF-8'
       LC_COLLATE='en_US.UTF-8'
       LC_CTYPE='en_US.UTF-8';

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


INSERT INTO users (name, email, password, cat_id) VALUES
( 'Yoda', 'yoda@jedi.net', '1234', 1 ),
( 'Luke', 'luke.theforce.net', 4321, 2 ),
( 'Vader', 'vader@darkside.net', 'asdf', 1 );

CREATE ROLE ecommaster WITH
    LOGIN PASSWORD '1234'
    CREATEDB REPLICATION
    VALID UNTIL 'infinity';



