<?php

declare(strict_types=1);

namespace Glued\Controllers;

use Firebase\JWT\ExpiredException;
use Glued\Lib\Exceptions\InternalException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Glued\Classes\Exceptions\AuthTokenException;
use Glued\Classes\Exceptions\AuthJwtException;
use Glued\Classes\Exceptions\AuthOidcException;
use Glued\Classes\Exceptions\DbException;
use Glued\Classes\Exceptions\TransformException;
use Symfony\Component\Config\Definition\Exception\Exception;


class ServiceController extends AbstractController
{

    /**
     * Returns a health status response.
     * @param  Request  $request  
     * @param  Response $response 
     * @param  array    $args     
     * @return Response Json result set.
     */
    public function health(Request $request, Response $response, array $args = []): Response {
        $params = $request->getQueryParams();
        $data = [
                'timestamp' => microtime(),
                'params' => $params,
                'service' => basename(__ROOT__),
                'provided-for' => $_SERVER['X-GLUED-AUTH-UUID'] ?? 'anon',
            ];
        return $response->withJson($data);
    }


    private function accounts($account = null): mixed
    {
        $wq = "";
        $wd = [];
        if ($account) { $wq = " AND uuid = uuid_to_bin(?, true)"; $wd[] = $account; }
        $q = "SELECT 
                bin_to_uuid(acc.uuid, true) as uuid,
                acc.data
              FROM t_settlement_accounts acc 
              WHERE 1=1 {$wq}";
        $res = $this->mysqli->execute_query($q, $wd);
        //return $res->fetch_all(MYSQLI_ASSOC);
        foreach ($res as $k=>$i) {
            $d[$k] = $i;
            $d[$k]['data'] = json_decode($i['data'], true);
        }
        return $d ?? false;
    }

    private function transactions($trx = false, $args = []): mixed
    {
        $wq = "";
        $wd = [];
        $args = array_merge(...array_values($args));
        //print_r($trx);
        //print_r($args);
        // Get a single transaction
        if ($trx) {
            $wq .= " AND uuid = uuid_to_bin(?, true)";
            $wd[] = $trx;
        }
        // Query for transactions
        else {
            // Separate numeric and date arguments
            $dateArgs = array_filter($args, function($arg) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $arg); });
            $numericArgs = array_filter($args, 'is_numeric');

            // Handle non-numeric and non-date arguments for the reference field using LIKE
            foreach ($args as $arg) {
                if (!is_numeric($arg) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $arg)) {
                    $wq .= " AND JSON_SEARCH(trx.data, 'all', ?);";
                    $wd[] = "%" . $arg . "%";
                }
            }

            // Handle numeric arguments for volume and reference
            if (!empty($numericArgs)) {
                $numericConditions = [];
                foreach ($numericArgs as $numericArg) {
                    $numericConditions[] = "(volume = ? OR ROUND(volume) = ? OR reference = ?)";
                    array_push($wd, $numericArg, $numericArg, $numericArg);
                }
                $wq .= " AND (" . implode(' OR ', $numericConditions) . ")";
            }

            // Handle date arguments for at with a +/- 5 days range
            if (!empty($dateArgs)) {
                $dateConditions = [];
                foreach ($dateArgs as $dateArg) {
                    $dateStart = date('Y-m-d', strtotime($dateArg . ' -5 days'));
                    $dateEnd = date('Y-m-d', strtotime($dateArg . ' +5 days'));
                    $dateConditions[] = "at BETWEEN ? AND ?";
                    array_push($wd, $dateStart, $dateEnd);
                }
                $wq .= " AND (" . implode(' OR ', $dateConditions) . ")";
            }

        }

        $q = "SELECT
                acc.ext_fid as account,
                bin_to_uuid(trx.uuid, true) as uuid,
                trx.data
              FROM t_settlement_transactions trx
              LEFT JOIN t_settlement_accounts acc ON trx.account = acc.uuid
              WHERE 1=1 {$wq}";
//        echo $q; die();

        $res = $this->mysqli->execute_query($q, $wd);
        foreach ($res as $k=>$i) {
            $d[$k] = $i;
            $d[$k]['data'] = json_decode($i['data'], true);
        }
        return $d ?? false;
    }

    /**
     * Returns a health status response.
     * @param  Request  $request
     * @param  Response $response
     * @param  array    $args
     * @return Response Json result set.
     */
    public function accounts_r1(Request $request, Response $response, array $args = []): Response
    {
        $data = $this->accounts($args['uuid'] ?? false);
        return $response->withJson(['data' => $data]);
    }

    public function transactions_r1(Request $request, Response $response, array $args = []): Response
    {
        $data = $this->transactions($args['uuid'] ?? false, $request->getQueryParams() ?? []);
        return $response->withJson(['data' => $data]);
    }


}
