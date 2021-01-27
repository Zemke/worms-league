create table "user" (
  id serial primary key,
  username text not null,
  email text not null
);

create unique index uidx_user_username on "user" (username);
create unique index uidx_user_email on "user" (email);

