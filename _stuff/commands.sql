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
ALTER TABLE users ADD COLUMN deleted SMALLINT DEFAULT 0;

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
CREATE TABLE user_session (
  id SERIAL NOT NULL PRIMARY KEY,
  user_id INT NOT NULL,
  hash VARCHAR(50) NOT NULL
);

ALTER TABLE user_session
ADD CONSTRAINT user_session_user_fkey FOREIGN KEY (user_id)
REFERENCES users (id) ON DELETE CASCADE;


-- Authentication tables
CREATE TABLE roles (
  id   SERIAL      NOT NULL PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);


CREATE TABLE permissions (
  description VARCHAR(50) NOT NULL PRIMARY KEY
);

CREATE TABLE role_perm (
  role_id INTEGER NOT NULL,
  perm_desc VARCHAR(50) NOT NULL,

  PRIMARY KEY (role_id, perm_desc),
  FOREIGN KEY (role_id) REFERENCES roles (id),
  FOREIGN KEY (perm_desc) REFERENCES permissions (description)
);

CREATE TABLE user_role (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,

  PRIMARY KEY (user_id, role_id),
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

INSERT INTO permissions (description) VALUES ('edit_other_users'), ('edit_roles');

INSERT INTO role_perm (role_id, perm_desc) VALUES (1, 'edit_other_users'), (1, 'edit_roles');
INSERT INTO user_role (role_id, user_id) VALUES (1, 1);

INSERT INTO permissions (description) VALUES ('edit_categories');

CREATE TABLE IF NOT EXISTS categories (
  id SERIAL NOT NULL,
  name VARCHAR(40) NOT NULL,
  description TEXT,
  img_w SMALLINT,
  img_h SMALLINT,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS posts (
  id SERIAL NOT NULL,
  category_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  title VARCHAR(200) NOT NULL,
  intro TEXT,
  post_text TEXT,
  image VARCHAR(80),
  image_caption VARCHAR(100),
  image_ext VARCHAR(10),
  status SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS department (
  id SERIAL NOT NULL,
  description VARCHAR(64) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS contact (
  id SERIAL NOT NULL,
  name VARCHAR(64) NOT NULL,
  email VARCHAR(64) NOT NULL,
  phone VARCHAR(18) NOT NULL,
  message TEXT NOT NULL,
  status SMALLINT NOT NULL,
  department_id INTEGER,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- If the post_id is null, then the
-- gallery will be treated as an entity
-- in itself, and not a part of another
-- content's page
CREATE TABLE IF NOT EXISTS galleries (
  id SERIAL NOT NULL,
  post_id INTEGER,
  image VARCHAR(35) NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id)
);
