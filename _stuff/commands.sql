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

CREATE EXTENSION unaccent;

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
  caption VARCHAR(200),
  position INTEGER,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id)
);

CREATE TABLE IF NOT EXISTS video_galleries (
  id SERIAL NOT NULL,
  post_id INTEGER,
  video_iframe VARCHAR(700) NOT NULL,
  title VARCHAR(200),
  position INTEGER,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE,
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id)
);

INSERT INTO permissions (description, created_at, updated_at) VALUES ('disable_own_user', now(), now());

ALTER TABLE posts DROP COLUMN category_id;

ALTER TABLE categories ALTER COLUMN id TYPE VARCHAR(32);

ALTER TABLE posts ADD COLUMN category_id VARCHAR(32);
ALTER TABLE posts ADD CONSTRAINT posts_category_id_fkey FOREIGN KEY (category_id) REFERENCES categories (id);

ALTER TABLE categories ADD COLUMN parent_id VARCHAR(32);
ALTER TABLE categories ADD CONSTRAINT categories_category_id_fkey FOREIGN KEY (parent_id) REFERENCES categories (id);

CREATE TABLE posts_categories (
  post_id INTEGER,
  category_id VARCHAR(32),
  PRIMARY KEY (post_id, category_id),
  FOREIGN KEY (post_id) REFERENCES posts(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Now that categories.id is a string, we have to drop the
-- default value and the sequence
ALTER TABLE categories ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS categories_id_seq;

ALTER TABLE posts DROP COLUMN category_id;

ALTER TABLE posts_categories DROP CONSTRAINT posts_categories_category_id_fkey,
  ADD CONSTRAINT posts_categories_category_id_fkey FOREIGN KEY (category_id) REFERENCES categories(id)
   ON UPDATE CASCADE;
ALTER TABLE posts ALTER COLUMN category_id SET NOT NULL;


-- It was agreed upon naming the image with post_id + image_id + size + extension,
-- like 103-1-thumb.jpg, 103-2-thumb.jpg, 103-1-large.jpg, 103-2-large.jpg, etc.
ALTER TABLE galleries DROP COLUMN image;

INSERT INTO galleries (post_id, position)
    SELECT 27, COALESCE(MAX(position), 0) + 1 FROM galleries;

ALTER TABLE video_galleries RENAME COLUMN video_iframe TO iframe;


ALTER TABLE galleries ADD COLUMN extension VARCHAR(4);


-- Refactored PHP to use “Images” instead of “Galleries”.
ALTER TABLE galleries RENAME TO images;

ALTER TABLE video_galleries RENAME TO videos;

CREATE TABLE contact (
  id SERIAL PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  email VARCHAR(64) NOT NULL,
  phone VARCHAR(18),
  message TEXT NOT NULL,
  status SMALLINT,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE
);

INSERT INTO contact (name, email, phone, message, status)
  VALUES ('Victor Sebben', 'victor@example.com', '5533444433', 'Hello. Fubar. Lorem ipsum dolor sit amet.', 1),
    ('Foo Bar', 'fubar@example.com', '5533443333', 'Hi. I agree that lorem ipsum.', 0),
    ('Fooson Barson', 'fooson@example.com', '55123123', 'Hello. Is everything OK?', 1);

CREATE TABLE agenda (
  id SERIAL PRIMARY KEY,
  date DATE,
  time TIME WITHOUT TIME ZONE,
  description TEXT,
  venue VARCHAR(64),
  city VARCHAR(64),
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE
);

ALTER TABLE videos RENAME COLUMN iframe TO video_id;
ALTER TABLE videos ADD COLUMN video_provider VARCHAR(100);
ALTER TABLE videos ADD COLUMN url VARCHAR(400);

CREATE TABLE series (
  id SERIAL PRIMARY KEY,
  title VARCHAR(200),
  status SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP WITHOUT TIME ZONE,
  updated_at TIMESTAMP WITHOUT TIME ZONE
);

ALTER TABLE series ADD COLUMN intro TEXT;

ALTER TABLE posts ADD COLUMN series_id INT;
ALTER TABLE posts
ADD CONSTRAINT posts_series_fkey FOREIGN KEY (series_id)
REFERENCES series (id);

ALTER TABLE posts ADD COLUMN position INT;

CREATE TABLE series_categories (
  series_id INT NOT NULL,
  category_id VARCHAR(32) NOT NULL,
  PRIMARY KEY (series_id, category_id),
  FOREIGN KEY (series_id) REFERENCES series(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);