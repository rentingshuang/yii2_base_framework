<?php

namespace ElemeOpenApi\Api;

/**
 * 餐厅入口流量服务
 */
class FlowService extends RpcService
{

    /** 根据时间段获取餐厅流量入口数据
     * @param $request 餐厅入口流量查询条件
     * @return mixed
     */
    public function get_entry_flow_stats_data($request)
    {
        return $this->client->call("eleme.flow.getEntryFlowStatsData", array("request" => $request));
    }

}