create table game (
  id serial primary key,
  home_id bigint not null,
  away_id bigint not null,
  home_score int,
  away_score int,
  created timestamp not null default now(),
  modified timestamp not null default now(),
  constraint fk_game_home_id_user foreign key (home_id) references "user" (id),
  constraint fk_game_away_id_user foreign key (away_id) references "user" (id)
);

create index uidx_game_home_id on game (home_id);
create index uidx_game_away_id on game (away_id);


create table replay (
  id serial primary key,
  game_id bigint not null,
  content bytea not null,
  created timestamp not null default now(),
  modified timestamp not null default now()
  constraint fk_replay_game_id foreign key (game_id) regerences game (id)
);

create index uidx_replay_game_id on replay (game_id);

