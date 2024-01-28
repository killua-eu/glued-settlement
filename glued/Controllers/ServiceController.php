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

    private function transactions($trx = null): mixed
    {
        $wq = "";
        $wd = [];
        $trx = $args['uuid'] ?? false;
        if ($trx) { $wq = " AND uuid = uuid_to_bin(?, true)"; $wd[] = $trx; }
        $q = "SELECT
                acc.ext_fid as account,
                bin_to_uuid(trx.uuid, true) as uuid,
                trx.data
              FROM t_settlement_transactions trx
              LEFT JOIN t_settlement_accounts acc ON trx.account = acc.uuid
              WHERE 1=1";
        $res = $this->mysqli->execute_query($q, $wd);
        // return $res->fetch_all(MYSQLI_ASSOC);
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
        $data = $this->transactions($args['uuid'] ?? false);
        return $response->withJson(['data' => $data]);
    }


}
