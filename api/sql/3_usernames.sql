create table username (
  id serial primary key,
  user_id bigint not null,
  username text not null,
  constraint fk_username_user foreign key (user_id) references "user" (id)
);

comment on column username.username is 'username as it appeared in a WA replay file (name in WormNet)';

create unique index uidx_username on username (username);
create index fkidx_username on user_id (username);

-- These can be null when the user has not been claimed yet.
alter table "user" rename column username to account_name;
alter table "user" alter column account_name drop not null;
alter table "user" alter column email drop not null;
alter table "user" alter column password drop not null;

comment on column "user".account_name is 'The user has registered in the app and has therefore claimed its usernames';

