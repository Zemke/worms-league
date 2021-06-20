create table "user" (
  id serial primary key,
  username text not null,
  email text not null,
  password text not null,
  created timestamp not null default now(),
  modified timestamp not null default now()
);

create unique index uidx_user_username on "user" (username);
create unique index uidx_user_email on "user" (email);

