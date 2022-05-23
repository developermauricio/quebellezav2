SET IDENTITY_INSERT [dbo].[user_types] ON
INSERT [dbo].[user_types] ([id], [ident], [caption]) VALUES (1, CONVERT(TEXT, N'admin'), CONVERT(TEXT, N'Admin'))
INSERT [dbo].[user_types] ([id], [ident], [caption]) VALUES (2, CONVERT(TEXT, N'user'), CONVERT(TEXT, N'User'))
SET IDENTITY_INSERT [dbo].[user_types] OFF;

SET IDENTITY_INSERT [dbo].[festi_url_areas] ON
INSERT [dbo].[festi_url_areas] ([ident], [id]) VALUES (CONVERT(TEXT, N'backend'), 1)
SET IDENTITY_INSERT [dbo].[festi_url_areas] OFF;

SET IDENTITY_INSERT [dbo].[festi_settings] ON
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'JavaScript Version'), CONVERT(TEXT, N'js_version'), CONVERT(TEXT, N'1'), 3)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'Caption'), CONVERT(TEXT, N'site_caption'), CONVERT(TEXT, N'Festi'), 4)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'Auth Login Column Name'), CONVERT(TEXT, N'auth_login_column'), CONVERT(TEXT, N'login'), 6)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'Auth Password Column Name'), CONVERT(TEXT, N'auth_pass_column'), CONVERT(TEXT, N'pass'), 7)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'Users Table Name'), CONVERT(TEXT, N'users_table'), CONVERT(TEXT, N'users'), 8)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'User Types Column'), CONVERT(TEXT, N'auth_role_column'), CONVERT(TEXT, N'id_type'), 9)
INSERT [dbo].[festi_settings] ([caption], [name], [value], [id]) VALUES (CONVERT(TEXT, N'Table Name for User Types'), CONVERT(TEXT, N'users_types_table'), CONVERT(TEXT, N'user_types'), 10)
SET IDENTITY_INSERT [dbo].[festi_settings] OFF;

SET IDENTITY_INSERT [dbo].[festi_plugins] ON
INSERT [dbo].[festi_plugins] ([status], [ident], [id]) VALUES (CONVERT(TEXT, N'active'), CONVERT(TEXT, N'Jimbo'), 1)
SET IDENTITY_INSERT [dbo].[festi_plugins] OFF;

SET IDENTITY_INSERT [dbo].[users] ON
INSERT [dbo].[users] ([id], [id_type], [login], [pass]) VALUES (1, 1, CONVERT(TEXT, N'admin'), CONVERT(TEXT, N'21232f297a57a5a743894a0e4a801fc3'))
SET IDENTITY_INSERT [dbo].[users] OFF;

SET IDENTITY_INSERT [dbo].[festi_url_rules] ON
INSERT [dbo].[festi_url_rules] ([plugin], [pattern], [method], [id]) VALUES (CONVERT(TEXT, N'Jimbo'), CONVERT(TEXT, N'~^/$~'), CONVERT(TEXT, N'onDisplayDefault'), 1)
INSERT [dbo].[festi_url_rules] ([plugin], [pattern], [method], [id]) VALUES (CONVERT(TEXT, N'Jimbo'), CONVERT(TEXT, N'~^/login/$~'), CONVERT(TEXT, N'onDisplaySignin'), 2)
INSERT [dbo].[festi_url_rules] ([plugin], [pattern], [method], [id]) VALUES (CONVERT(TEXT, N'Jimbo'), CONVERT(TEXT, N'~^/logout/$~'), CONVERT(TEXT, N'onDisplayLogout'), 3)
SET IDENTITY_INSERT [dbo].[festi_url_rules] OFF;

SET IDENTITY_INSERT [dbo].[festi_menus] ON
INSERT [dbo].[festi_menus] ([id], [caption], [url], [id_parent], [order_n], [description], [id_section], [area]) VALUES (5, CONVERT(TEXT, N'Invoices'), CONVERT(TEXT, N'/invoices/'), NULL, 1, NULL, NULL, NULL)
SET IDENTITY_INSERT [dbo].[festi_menus] OFF;

SET IDENTITY_INSERT [dbo].[festi_url_rules2areas] ON
INSERT [dbo].[festi_url_rules2areas] ([id_url_rule], [area], [id]) VALUES (1, CONVERT(TEXT, N'backend'), 1)
INSERT [dbo].[festi_url_rules2areas] ([id_url_rule], [area], [id]) VALUES (2, CONVERT(TEXT, N'backend'), 2)
INSERT [dbo].[festi_url_rules2areas] ([id_url_rule], [area], [id]) VALUES (3, CONVERT(TEXT, N'backend'), 3)
SET IDENTITY_INSERT [dbo].[festi_url_rules2areas] OFF;

SET IDENTITY_INSERT [dbo].[festi_menu_permissions] ON
INSERT [dbo].[festi_menu_permissions] ([id], [id_role], [id_menu]) VALUES (6, 1, 5)
INSERT [dbo].[festi_menu_permissions] ([id], [id_role], [id_menu]) VALUES (7, 2, 5)
SET IDENTITY_INSERT [dbo].[festi_menu_permissions] OFF;