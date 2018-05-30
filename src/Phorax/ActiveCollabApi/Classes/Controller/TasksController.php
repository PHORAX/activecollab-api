<?php
declare(strict_types = 1);

namespace Phorax\ActiveCollabApi\Controller;

class TasksController {

    /**
     * Task POST action
     *
     * @param $optione array
     *
     * @return array
     */
    public function getAction($options): array {
        $taskId = $options['id'];
        $query = 'SELECT id, project_id AS project FROM tasks WHERE id = ? LIMIT 1';
        $statement = $GLOBALS['db']->prepare($query);
        $statement->execute([ $taskId ]);
        $task = $statement->fetch();
        return ($task === false) ? [] : $task;
    }
}