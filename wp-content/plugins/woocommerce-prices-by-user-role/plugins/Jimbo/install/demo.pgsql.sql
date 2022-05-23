INSERT INTO "festi_plugins" ("id", "status", "ident") VALUES
(DEFAULT, 'active', 'Jimbo');

INSERT INTO "festi_url_areas" ("id", "ident") VALUES
(3, 'backend');

INSERT INTO "festi_url_rules" ("id", "plugin", "pattern", "method") VALUES
(2, 'Jimbo', '~^/$~', 'onDisplayDefault'),
(3, 'Jimbo', '~^/login/$~', 'onDisplaySignin'),
(4, 'Jimbo', '~^/logout/$~', 'onDisplayLogout');

INSERT INTO "festi_url_rules2areas" ("id", "id_url_rule", "area") VALUES
(1, 2, 'backend'),
(2, 3, 'backend'),
(3, 4, 'backend');

INSERT INTO "users_types" ("id", "caption", "ident") VALUES
(1, 'Admin', 'admin'),
(2, 'User', 'user');

INSERT INTO "users" ("id", "id_type", "name", "lastname", "login", "pass", "email") VALUES
(1, 1, NULL, NULL, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'demo@test.com');

INSERT INTO "festi_settings" ("id", "caption", "name", "value") VALUES
(5, '', 'auth_login_column', 'login'),
(6, '', 'auth_pass_column', 'pass'),
(7, '', 'users_table', 'users'),
(8, '', 'users_types_table', 'users_types'),
(9, '', 'auth_role_column', 'id_type'),
(10, '', 'js_version', '1'),
(11, '', 'site_caption', 'Festi');