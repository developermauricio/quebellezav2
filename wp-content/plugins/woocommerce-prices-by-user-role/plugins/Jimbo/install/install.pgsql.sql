DROP TABLE IF EXISTS "festi_listeners";
DROP TABLE IF EXISTS "festi_menu_permissions";
DROP TABLE IF EXISTS "festi_menus";
DROP TABLE IF EXISTS "festi_sections_user_types_permission";
DROP TABLE IF EXISTS "festi_sections_user_permission";
DROP TABLE IF EXISTS "festi_section_actions";
DROP TABLE IF EXISTS "festi_sections";
DROP TABLE IF EXISTS "festi_url_rules2areas";
DROP TABLE IF EXISTS "festi_url_areas";
DROP TABLE IF EXISTS "festi_url_rules";
DROP TABLE IF EXISTS "festi_plugins";
DROP TABLE IF EXISTS "festi_texts";
DROP TABLE IF EXISTS "festi_settings";
DROP TABLE IF EXISTS "users";
DROP TABLE IF EXISTS "users_types";

DROP SEQUENCE IF EXISTS "festi_plugins_id_seq";

DROP TYPE IF EXISTS "festi_plugins_status";
DROP TYPE IF EXISTS "festi_section_actions_mask";
DROP TYPE IF EXISTS "festi_sections_mask";

CREATE TABLE "festi_listeners" (
    "id" SERIAL PRIMARY KEY,
    "plugin" varchar(64) DEFAULT NULL,
    "method" varchar(128) DEFAULT NULL,
    "callback_plugin" varchar(64) DEFAULT NULL,
    "callback_method" varchar(128) DEFAULT NULL,
    "url_area" varchar(64) DEFAULT NULL
);

CREATE TYPE festi_plugins_status AS ENUM ('active','hidden'); 
CREATE TABLE "festi_plugins" (
    "id" SERIAL PRIMARY KEY,
    "status" festi_plugins_status NOT NULL DEFAULT 'active',
    "ident" varchar(64) NOT NULL,
    UNIQUE ("ident")
);

CREATE TABLE "festi_menus" (
  "id" SERIAL PRIMARY KEY,
  "caption" character varying(64) NOT NULL,
  "url" character varying(64) DEFAULT NULL,
  "id_parent" int4 DEFAULT NULL,
  "order_n" integer DEFAULT NULL,
  "description" character varying(128) DEFAULT NULL,
  "id_section" int4  DEFAULT NULL,
  "area" character varying(32) DEFAULT NULL,
  "ident" character varying(32) DEFAULT NULL
);

CREATE TABLE "festi_menu_permissions" (
  "id" SERIAL PRIMARY KEY,
  "id_role" int4  NOT NULL,
  "id_menu" int4 NOT NULL
);

CREATE TYPE festi_section_actions_mask AS ENUM ('2','4','6'); 
CREATE TABLE "festi_section_actions" (
    "id" SERIAL PRIMARY KEY,
    "id_section" integer  NOT NULL,
    "plugin" varchar(64) NOT NULL,
    "method" varchar(128) NOT NULL,
    "mask" festi_section_actions_mask NOT NULL DEFAULT '2',
    "comment" varchar(510) DEFAULT NULL,
    UNIQUE ("plugin","method","id_section")
);

CREATE TYPE festi_sections_mask AS ENUM ('2','4','6'); 
CREATE TABLE "festi_sections" (
    "id" SERIAL PRIMARY KEY,
    "caption" varchar(128) NOT NULL,
    "ident" varchar(64) NOT NULL,
    "mask" festi_sections_mask NOT NULL DEFAULT '2'
);

CREATE TABLE "festi_sections_user_permission" (
    "id" SERIAL PRIMARY KEY,
    "id_section" int4  NOT NULL,
    "id_user" int4  NOT NULL,
    "value" integer  NOT NULL,
    UNIQUE ("id_section","id_user")
);

CREATE TABLE "festi_sections_user_types_permission" (
    "id" SERIAL PRIMARY KEY,
    "id_section" int4  NOT NULL,
    "id_user_type" int4  NOT NULL,
    "value" integer NOT NULL DEFAULT '2'
);

CREATE TABLE "festi_settings" (
    "id" SERIAL PRIMARY KEY,
    "caption" varchar(510) NOT NULL,
    "name" varchar(64) NOT NULL,
    "value" text NOT NULL,
    UNIQUE ("name")
);

CREATE TABLE "festi_texts" (
    "id" SERIAL PRIMARY KEY,
    "ident" varchar(64) NOT NULL,
    "text" text NOT NULL
);

CREATE TABLE "festi_url_areas" (
    "id" SERIAL PRIMARY KEY,
    "ident" varchar(64) NOT NULL,
    UNIQUE ("ident")
);

CREATE TABLE "festi_url_rules" (
    "id" SERIAL PRIMARY KEY,
    "plugin" varchar(64) NOT NULL,
    "pattern" varchar(510) NOT NULL,
    "method" varchar(128) NOT NULL
);

CREATE TABLE "festi_url_rules2areas" (
    "id" SERIAL PRIMARY KEY,
    "id_url_rule" int4  NOT NULL,
    "area" varchar(64) NOT NULL,
    UNIQUE ("id_url_rule","area")
);

CREATE TABLE "users" (
    "id" SERIAL PRIMARY KEY,
    "id_type" int4  NOT NULL,
    "name" varchar(256) DEFAULT NULL,
    "lastname" varchar(128) DEFAULT NULL,
    "login" varchar(256) DEFAULT NULL,
    "pass" varchar(64) NOT NULL,
    "email" varchar(256) DEFAULT NULL,
    "skype" varchar(500) DEFAULT NULL,
    "cdate" timestamp DEFAULT NULL,
    "is_send_invite" integer DEFAULT NULL,
    "mdate" timestamp DEFAULT NULL,
    "access_token" varchar(510) DEFAULT NULL,
    UNIQUE ("email")
);

CREATE TABLE "users_types" (
    "id" SERIAL PRIMARY KEY,
    "caption" varchar(256) NOT NULL,
    "ident" varchar(256) NOT NULL,
    UNIQUE ("ident")
);

ALTER TABLE "festi_listeners" ADD CONSTRAINT "festi_listeners_ibfk_1" FOREIGN KEY ("plugin") REFERENCES "festi_plugins" ("ident") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_listeners" ("plugin");
ALTER TABLE "festi_listeners" ADD CONSTRAINT "festi_listeners_ibfk_2" FOREIGN KEY ("callback_plugin") REFERENCES "festi_plugins" ("ident") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_listeners" ("callback_plugin");
ALTER TABLE "festi_listeners" ADD CONSTRAINT "festi_listeners_ibfk_3" FOREIGN KEY ("url_area") REFERENCES "festi_url_areas" ("ident") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_listeners" ("url_area");

CREATE INDEX ON "festi_menus" ("id_section");
ALTER TABLE "festi_menus" ADD CONSTRAINT "festi_menus_ibfk_1" FOREIGN KEY ("id_section") REFERENCES "festi_sections" ("id") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;

CREATE INDEX ON "festi_menus" ("area");

CREATE INDEX ON "festi_menu_permissions" ("id_role");
ALTER TABLE "festi_menu_permissions" ADD CONSTRAINT "festi_menu_permissions_ibfk_1" FOREIGN KEY ("id_role") REFERENCES "users_types" ("id") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;

CREATE INDEX ON "festi_menu_permissions" ("id_menu");
ALTER TABLE "festi_menu_permissions" ADD CONSTRAINT "festi_menu_permissions_ibfk_2" FOREIGN KEY ("id_menu") REFERENCES "festi_menus" ("id") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;

ALTER TABLE "festi_section_actions" ADD CONSTRAINT "festi_section_actions_ibfk_1" FOREIGN KEY ("id_section") REFERENCES "festi_sections" ("id") ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_section_actions" ("id_section");
ALTER TABLE "festi_section_actions" ADD CONSTRAINT "festi_section_actions_ibfk_2" FOREIGN KEY ("plugin") REFERENCES "festi_plugins" ("ident") ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_section_actions" ("plugin");
ALTER TABLE "festi_sections_user_permission" ADD CONSTRAINT "festi_sections_user_permission_ibfk_2" FOREIGN KEY ("id_user") REFERENCES "users" ("id") ON DELETE CASCADE ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_sections_user_permission" ("id_user");
ALTER TABLE "festi_sections_user_permission" ADD CONSTRAINT "festi_sections_user_permission_ibfk_3" FOREIGN KEY ("id_section") REFERENCES "festi_sections" ("id") ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_sections_user_permission" ("id_section");
ALTER TABLE "festi_sections_user_types_permission" ADD CONSTRAINT "festi_sections_user_types_permission_ibfk_1" FOREIGN KEY ("id_section") REFERENCES "festi_sections" ("id") ON DELETE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_sections_user_types_permission" ("id_section");
ALTER TABLE "festi_sections_user_types_permission" ADD CONSTRAINT "festi_sections_user_types_permission_ibfk_2" FOREIGN KEY ("id_user_type") REFERENCES "users_types" ("id") ON DELETE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_sections_user_types_permission" ("id_user_type");
ALTER TABLE "festi_url_rules" ADD CONSTRAINT "festi_url_rules_ibfk_1" FOREIGN KEY ("plugin") REFERENCES "festi_plugins" ("ident") ON DELETE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_url_rules" ("plugin");
ALTER TABLE "festi_url_rules2areas" ADD CONSTRAINT "festi_url_rules2areas_ibfk_1" FOREIGN KEY ("area") REFERENCES "festi_url_areas" ("ident") ON UPDATE NO ACTION DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "festi_url_rules2areas" ("area");

ALTER TABLE "users" ADD CONSTRAINT "users_ibfk_1" FOREIGN KEY ("id_type") REFERENCES "users_types" ("id") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "users" ("id_type");

