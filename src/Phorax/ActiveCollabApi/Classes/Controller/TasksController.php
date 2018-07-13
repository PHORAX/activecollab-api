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
        if (!intval($options['id'])) {
            http_response_code(403);
            echo json_encode([ 'error' => 'ticket.id invalid' ]);
            exit;
        }

        /* @TODO: Check privileges */

        $query = 'SELECT id AS id, project_id AS project FROM tasks WHERE id = :id LIMIT 1';
        /** @var \PDOStatement $statement */
        $statement = $GLOBALS['db']->prepare($query);
        $statement->bindParam(':id', $options['id']);
        $statement->execute();
        $task = $statement->fetch(\PDO::FETCH_ASSOC);
        return ($task === false) ? [] : $task;
    }
}