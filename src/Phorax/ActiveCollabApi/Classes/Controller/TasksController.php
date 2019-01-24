<?php
declare(strict_types=1);

namespace Phorax\ActiveCollabApi\Controller;

class TasksController
{

    /**
     * Task POST action
     *
     * @param $optione array
     *
     * @return array
     */
    public function getAction($options): string
    {
        if (!intval($options['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'id invalid']);
            exit;
        }

        $query = 'SELECT
                	tasks.id AS id,
                	tasks.project_id AS project,
                	(
                		SELECT COUNT(*)
                		FROM project_users
        		        WHERE tasks.project_id = project_users.project_id
        		        AND project_users.user_id = :userId
                    ) AS access_project
                FROM tasks
                WHERE tasks.id = :taskId
                LIMIT 1';

        /**
         * @var \PDOStatement $statement
         */
        $statement = $GLOBALS['db']->prepare($query);
        $statement->bindParam(':taskId', $options['id']);
        $statement->bindParam(':userId', $GLOBALS['user']['id']);
        if ($statement->execute() === false) {
            return '';
        }
        $task = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($GLOBALS['user']['type'] == 'Owner' || $task['access_project'] == 1) {
            return json_encode([ 'id' => $task['id'], 'project' => $task['project'] ]);
        }
        return '';
    }
}
