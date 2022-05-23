/****** Object:  Table [dbo].[user_types]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[user_types](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[ident] [varchar](32) NOT NULL,
	[caption] [varchar](255) NOT NULL,
 CONSTRAINT [PK_user_types] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_url_areas]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_url_areas](
	[ident] [varchar](32) NOT NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_url_areas] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX [IX_festi_url_areas] ON [dbo].[festi_url_areas]
(
	[ident] ASC
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_texts]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_texts](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[ident] [varchar](32) NOT NULL,
	[text] [text] NOT NULL,
 CONSTRAINT [PK_festi_texts] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_settings]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_settings](
	[caption] [varchar](50) NOT NULL,
	[name] [varchar](50) NOT NULL,
	[value] [text] NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_settings] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX [IX_festi_settings] ON [dbo].[festi_settings]
(
	[name] ASC
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_sections]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_sections](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[caption] [varchar](64) NOT NULL,
	[ident] [varchar](32) NOT NULL,
	[mask] [int] NOT NULL,
 CONSTRAINT [PK_festi_sections] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX [IX_festi_sections_ident] ON [dbo].[festi_sections]
(
	[ident] ASC
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_section_actions]    Script Date: 21/03/2019 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_section_actions](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[id_section] [int] NOT NULL,
	[plugin] [varchar](32) NOT NULL,
	[method] [varchar](64) NOT NULL,
	[mask] [varchar](1) NOT NULL CHECK (mask IN('2', '4', '6')),
	[comment] [varchar](255) NOT NULL,
 CONSTRAINT [PK_festi_section_actions] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_plugins]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_plugins](
	[status] [varchar](26) NOT NULL,
	[ident] [varchar](32) NOT NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_plugins] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX [IX_festi_plugins_ident] ON [dbo].[festi_plugins]
(
	[ident] ASC
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_listeners]    Script Date: 12/6/2017 3:55:39 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_listeners](
	[plugin] [varchar](32) NOT NULL,
	[method] [varchar](64) NOT NULL,
	[callback_plugin] [varchar](32) NOT NULL,
	[callback_method] [varchar](64) NOT NULL,
	[url_area] [varchar](32) NOT NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_listeners] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

CREATE NONCLUSTERED INDEX [IX_festi_listeners_plugin] ON [dbo].[festi_listeners]
(
	[plugin] ASC
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[users]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[users](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[id_type] [int] NOT NULL,
	[login] [varchar](32) NOT NULL,
	[pass] [varchar](32) NOT NULL,
 CONSTRAINT [PK_users] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_url_rules]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_url_rules](
	[plugin] [varchar](32) NOT NULL,
	[pattern] [varchar](255) NOT NULL,
	[method] [varchar](64) NOT NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_url_rules] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_menus]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_menus](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[caption] [varchar](64) NOT NULL,
	[url] [varchar](255) NULL,
	[id_parent] [int] NULL,
	[order_n] [int] NULL,
	[description] [varchar](128) NULL,
	[id_section] [int] NULL,
	[area] [varchar](32) NULL,
 CONSTRAINT [PK_festi_menus] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_sections_user_types_permission]    Script Date: 08/21/2015 00:04:23 ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

CREATE TABLE [dbo].[festi_sections_user_types_permission](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[id_section] [int] NOT NULL,
	[id_user_type] [int] NOT NULL,
	[value] [int] NOT NULL,
 CONSTRAINT [PK_festi_sections_user_types_permission] PRIMARY KEY CLUSTERED
(
	[id] ASC
) ON [PRIMARY]
) ON [PRIMARY];
/****** Object:  Table [dbo].[festi_url_rules2areas]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_url_rules2areas](
	[id_url_rule] [int] NULL,
	[area] [varchar](32) NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_festi_url_rules2areas] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_sections_user_permission]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;

CREATE TABLE [dbo].[festi_sections_user_permission](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[id_section] [int] NOT NULL,
	[id_user] [int] NOT NULL,
	[value] [int] NOT NULL,
 CONSTRAINT [PK_festi_sections_user_permission] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];

SET ANSI_PADDING OFF;
/****** Object:  Table [dbo].[festi_menu_permissions]    Script Date: 12/6/2017 3:55:40 PM ******/
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

CREATE TABLE [dbo].[festi_menu_permissions](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[id_role] [int] NOT NULL,
	[id_menu] [int] NOT NULL,
 CONSTRAINT [PK_festi_menu_permissions] PRIMARY KEY CLUSTERED
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY];
/****** Table relations ******/
ALTER TABLE [dbo].[festi_listeners]  WITH CHECK ADD  CONSTRAINT [FK_festi_listeners_festi_plugins] FOREIGN KEY([plugin])
REFERENCES [dbo].[festi_plugins] ([ident]);

ALTER TABLE [dbo].[festi_listeners] CHECK CONSTRAINT [FK_festi_listeners_festi_plugins];

ALTER TABLE [dbo].[festi_menu_permissions]  WITH CHECK ADD  CONSTRAINT [FK_festi_menu_permissions_festi_menus] FOREIGN KEY([id_menu])
REFERENCES [dbo].[festi_menus] ([id]);

ALTER TABLE [dbo].[festi_menu_permissions] CHECK CONSTRAINT [FK_festi_menu_permissions_festi_menus];

ALTER TABLE [dbo].[festi_menu_permissions]  WITH CHECK ADD  CONSTRAINT [FK_festi_menu_permissions_user_types] FOREIGN KEY([id_role])
REFERENCES [dbo].[user_types] ([id]);

ALTER TABLE [dbo].[festi_menu_permissions] CHECK CONSTRAINT [FK_festi_menu_permissions_user_types];

ALTER TABLE [dbo].[festi_menus]  WITH CHECK ADD  CONSTRAINT [FK_festi_menus_festi_menus] FOREIGN KEY([id_parent])
REFERENCES [dbo].[festi_menus] ([id]);

ALTER TABLE [dbo].[festi_menus] CHECK CONSTRAINT [FK_festi_menus_festi_menus];

ALTER TABLE [dbo].[festi_menus]  WITH CHECK ADD  CONSTRAINT [FK_festi_menus_festi_sections] FOREIGN KEY([id_section])
REFERENCES [dbo].[festi_sections] ([id]);

ALTER TABLE [dbo].[festi_menus] CHECK CONSTRAINT [FK_festi_menus_festi_sections];

ALTER TABLE [dbo].[festi_menus]  WITH CHECK ADD  CONSTRAINT [FK_festi_menus_festi_url_areas] FOREIGN KEY([area])
REFERENCES [dbo].[festi_url_areas] ([ident]);

ALTER TABLE [dbo].[festi_menus] CHECK CONSTRAINT [FK_festi_menus_festi_url_areas];

ALTER TABLE [dbo].[festi_sections_user_permission]  WITH CHECK ADD  CONSTRAINT [FK_festi_sections_user_permission_festi_sections] FOREIGN KEY([id_section])
REFERENCES [dbo].[festi_sections] ([id]);

ALTER TABLE [dbo].[festi_sections_user_permission] CHECK CONSTRAINT [FK_festi_sections_user_permission_festi_sections];

ALTER TABLE [dbo].[festi_sections_user_permission]  WITH CHECK ADD  CONSTRAINT [FK_festi_sections_user_permission_users] FOREIGN KEY([id_user])
REFERENCES [dbo].[users] ([id]);

ALTER TABLE [dbo].[festi_sections_user_permission] CHECK CONSTRAINT [FK_festi_sections_user_permission_users];

ALTER TABLE [dbo].[festi_sections_user_types_permission]  WITH CHECK ADD  CONSTRAINT [FK_festi_sections_user_types_permission_festi_sections] FOREIGN KEY([id_section])
REFERENCES [dbo].[festi_sections] ([id]);

ALTER TABLE [dbo].[festi_sections_user_types_permission] CHECK CONSTRAINT [FK_festi_sections_user_types_permission_festi_sections];

ALTER TABLE [dbo].[festi_sections_user_types_permission]  WITH CHECK ADD  CONSTRAINT [FK_festi_sections_user_types_permission_user_types] FOREIGN KEY([id_user_type])
REFERENCES [dbo].[user_types] ([id]);

ALTER TABLE [dbo].[festi_sections_user_types_permission] CHECK CONSTRAINT [FK_festi_sections_user_types_permission_user_types];

ALTER TABLE [dbo].[festi_url_rules]  WITH CHECK ADD  CONSTRAINT [FK_festi_url_rules_festi_plugins] FOREIGN KEY([plugin])
REFERENCES [dbo].[festi_plugins] ([ident]);

ALTER TABLE [dbo].[festi_url_rules] CHECK CONSTRAINT [FK_festi_url_rules_festi_plugins];

ALTER TABLE [dbo].[festi_url_rules2areas]  WITH CHECK ADD  CONSTRAINT [FK_festi_url_rules2areas_festi_url_areas] FOREIGN KEY([area])
REFERENCES [dbo].[festi_url_areas] ([ident]);

ALTER TABLE [dbo].[festi_url_rules2areas] CHECK CONSTRAINT [FK_festi_url_rules2areas_festi_url_areas];

ALTER TABLE [dbo].[festi_url_rules2areas]  WITH CHECK ADD  CONSTRAINT [FK_festi_url_rules2areas_festi_url_rules] FOREIGN KEY([id_url_rule])
REFERENCES [dbo].[festi_url_rules] ([id]);

ALTER TABLE [dbo].[festi_url_rules2areas] CHECK CONSTRAINT [FK_festi_url_rules2areas_festi_url_rules];

ALTER TABLE [dbo].[users]  WITH CHECK ADD  CONSTRAINT [FK_users_user_types] FOREIGN KEY([id_type])
REFERENCES [dbo].[user_types] ([id]);

ALTER TABLE [dbo].[users] CHECK CONSTRAINT [FK_users_user_types];