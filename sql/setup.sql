create table if not exists accesskey
(
    id          int auto_increment
        primary key,
    name        varchar(255) not null,
    description varchar(255) null,
    uuid        varchar(64)  not null,
    user_id     int          null,
    constraint key_uuid_uindex
        unique (uuid),
    constraint key_user_id_fk
        foreign key (user_id) references user (id)
            on delete cascade
);

create table if not exists accesskey_permissions
(
    id            int auto_increment
        primary key,
    key_id        int      not null,
    dataset_id    int      null,
    permission    char     not null,
    date_created  datetime null,
    date_modified datetime null,
    constraint accesskey_permissions_accesskey_id_fk
        foreign key (key_id) references accesskey (id)
            on delete cascade,
    constraint key_permissions_dataset_id_fk
        foreign key (dataset_id) references dataset (id)
            on delete cascade
);