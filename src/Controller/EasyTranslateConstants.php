<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Controller;

class EasyTranslateConstants
{
    const LOG_CHANNEL = 'easytranslate';

    const EVENT_TASK_UPDATE = 'task.updated';
    const EVENT_PROJECT_STATUS_APPROVAL = 'project.status.approval_needed';
    const EVENT_PROJECT_STATUS_ACCEPTED = 'project.status.price_accepted';
    const EVENT_PROJECT_STATUS_DECLINED = 'project.status.price_declined';

    const PROJECT_STATUS_INIT = 'INIT';
    const PROJECT_STATUS_CREATED = 'CREATED';
    const PROJECT_STATUS_APPROVAL_NEEDED = 'APPROVAL_NEEDED';
    const PROJECT_STATUS_APPROVED = 'APPROVED';
    const PROJECT_STATUS_DECLINED = 'DECLINED';
    const PROJECT_STATUS_COMPLETED = 'COMPLETED';
}
