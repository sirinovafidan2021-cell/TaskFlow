# Rollar, permissions və authorization

Authentication istifadəçinin kimliyini yoxlayır; authorization isə onun konkret əməliyyata icazəsini müəyyənləşdirir. TaskFlow Spatie Permission və Laravel Policy-lərini birlikdə istifadə edir.

RolePermissionSeeder admin, project_manager və member rollarını yaradır. Admin bütün permissions-ları alır. Project Manager user role/API token idarəsi istisna olmaqla biznes permissions-larına malikdir. Member yalnız aşağıdakı məhdud permissions-ları alır: projects.view, tasks.view, tasks.status.change, comments.create, attachments.upload, dashboard.view.

| Permission | İdarə etdiyi sahə |
| --- | --- |
| users.roles.manage, api_tokens.manage | Host istifadəçi/API imkanları. |
| projects.view, create, update, archive, members.manage | Layihə oxuma, yaratma, yeniləmə, status və üzvlük. |
| tasks.view, create, update, assign, status.change, delete | Task həyat dövrü. |
| comments.create, comments.delete | Task şərhləri. |
| attachments.upload, attachments.delete | Task faylları. |
| activity.view, dashboard.view | Jurnal və dashboard. |

ProjectPolicy viewAny, create, view, update, archive, delete, manageMembers metodlarına malikdir. TaskPolicy viewAny, create, view, update, delete, assign, changeStatus, comment, deleteComment, uploadAttachment, deleteAttachment metodlarını saxlayır. ActivityPolicy viewAny yoxlaması edir. Dashboard controller viewDashboard authorization çağırır.

Controller-lər authorize çağırışı edir; API route-ları auth:sanctum və api.verified, web biznes route-ları auth və verified ilə qorunur. Permission geniş capability-ni, Policy isə konkret project/task və üzvlük münasibətini yoxlayır.
