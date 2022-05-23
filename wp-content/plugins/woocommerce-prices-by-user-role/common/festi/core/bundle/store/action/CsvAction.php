<?php

use core\dgs\action\StoreActionException;

class CsvAction extends AbstractAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $action = $this->model->getAction(Store::ACTION_CSV);

        if (!$action) {
            throw new StoreActionException("Undefined action into DGS");
        }

        $fileName = "export_";
        if (array_key_exists('fileName', $action)) {
            $fileName = $action['fileName'];
        }
        
        $fileName .= date('Ymd').".csv";
        
        $this->store->setUseLimit(false);
        $rows = $this->store->load();

        $fields = $this->model->getFields();

        $columns = array();
        foreach ($fields as $key => $field) {

            $name = $field->getName();
            if ($field instanceof Many2manyField) {
                $name = "__".$field->getIndex();
            }

            $columns[$name] = $field->getCaption();
        }

        $response->setAction(Response::ACTION_LAMBDA);

        $response->lambda = function () use ($fileName, $fields, $columns, $rows) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$fileName);

            $stream = fopen('php://output', 'w');

            fputcsv($stream, $columns);

            foreach ($rows as $sku => $row) {
                $data = array();

                foreach ($columns as $key => $caption) {
                    $value = $row[$key];

                    if (array_key_exists($key, $fields)) {
                        $field = $fields[$key];
                        if ($field->get('crypt')) {
                            $value = $field->getDecryptedValue($value);
                        }
                    }

                    $data[] = $value;
                }

                fputcsv($stream, $data);
            }

            fclose($stream);
            exit();
        };

        return true;
    } // end onStart
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_CSV;
    } // end getActionName
}