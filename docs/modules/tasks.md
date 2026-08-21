# Tasks modulu

## Məqsəd və struktur

Tasks modulu aktiv layihədə task yaratmaq, yeniləmək, silmək, təyin etmək, status dəyişmək, şərh və attachment idarə etmək üçündür.

Vacib fayllar: Models/Task.php, TaskComment.php, TaskAttachment.php; web TaskController, TaskCommentController, TaskAttachmentController; API TaskController; Create/Update/Assign/ChangeStatus/Comment/Upload request-ləri; TaskResource; TaskService, TaskAssignmentService, TaskStatusService, TaskCommentService, TaskAttachmentService; TaskPolicy; repository interface/Eloquent implementasiyaları; web/api route-ları; üç migration, üç factory və TaskApiTest/TaskWebTest.

## Model və database

tasks cədvəli project_id, creator_id, nullable assignee_id, unique number, title, description, status, priority, due_at, started_at, completed_at və soft delete saxlayır. Task project, creator və assignee ilə bağlıdır. task_comments task/user/body və soft delete; task_attachments task/uploader/disk/path/original name/MIME/size saxlayır.

Yeni task TaskService.create daxilində yalnız active project üçün yaranır. Təyin olunan user project member olmalıdır. Task əvvəlcə todo statusu ilə saxlanır, sonra ID-dən TSK-000001 formatında number verilir.

## Controller və request-lər

Web TaskController index, create, store, show, edit, update, destroy, assign, changeStatus metodlarına malikdir. Hər metod TaskPolicy authorize çağırır. TaskCommentController store/destroy, TaskAttachmentController store/download/destroy metodları ilə comment və faylı idarə edir.

API TaskController index, show, store, update, destroy metodlarını saxlayır. index TaskIndexRequest filterlərini, store StoreTaskRequest vasitəsilə project_id və CreateTaskRequest qaydalarını, update UpdateTaskRequest qaydalarını istifadə edir. API cavabları TaskResource-dur.

CreateTaskRequest title 3-180, description max 10000, optional assignee_id, enum priority və optional due_at yoxlayır. Update eyni dəyişən sahələri yoxlayır. AssignTaskRequest optional mövcud user assignee_id, ChangeTaskStatusRequest TaskStatus enum-u, CreateTaskCommentRequest body max 5000, UploadTaskAttachmentRequest isə attachment MIME siyahısı və maksimum 10240 KB yoxlayır.

## Servislər, status və icazə

TaskService create/update/delete və task activity-lərini aparır. TaskAssignmentService üzvlüyü yoxlayır və assignment activity yazır. TaskStatusService keçidləri idarə edir: todo → in_progress/cancelled; in_progress → todo/review/cancelled; review → in_progress/done; done/cancelled yalnız idarə edə bilən user tərəfindən yenidən açıla bilər. In progress started_at, done completed_at yazır. Comment və attachment servisləri transaction, storage və activity işini aparır.

TaskPolicy viewAny, create, view, update, delete, assign, changeStatus, comment, deleteComment, uploadAttachment, deleteAttachment metodları ilə permission və project membership yoxlayır. Permissions tasks.view/create/update/assign/status.change/delete, comments.create/delete, attachments.upload/delete-dir.

## Route, activity və test

Web route-ları /tasks, /projects/{project}/tasks/create və POST task, /tasks/{task} CRUD, assignee/status patch, comments və attachments əməliyyatlarını əhatə edir. API yalnız /api/v1/tasks CRUD-dur.

Activity event-ləri task.created, task.updated, task.deleted, task.assigned, task.status_changed, comment.created/deleted, attachment.uploaded/deleted-dir. TaskApiTest API CRUD, validation, project/assignee münasibəti, number, status/priority, authorization və soft delete; TaskWebTest qorunan səhifələri və web create axınını yoxlayır.
