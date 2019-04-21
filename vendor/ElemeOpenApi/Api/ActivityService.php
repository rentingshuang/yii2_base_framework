<?php

namespace ElemeOpenApi\Api;

/**
 * 活动服务
 */
class ActivityService extends RpcService
{

    /** 创建红包活动
     * @param $create_info 创建红包活动的结构体
     * @return mixed
     */
    public function create_coupon_activity($create_info)
    {
        return $this->client->call("eleme.activity.coupon.createCouponActivity", array("createInfo" => $create_info));
    }

    /** 向指定用户发放红包
     * @param $shop_id 店铺Id
     * @param $coupon_activity_id 红包活动Id
     * @param $mobiles 需要发放红包的用户手机号列表
     * @return mixed
     */
    public function give_out_coupons($shop_id, $coupon_activity_id, $mobiles)
    {
        return $this->client->call("eleme.activity.coupon.giveOutCoupons", array("shopId" => $shop_id, "couponActivityId" => $coupon_activity_id, "mobiles" => $mobiles));
    }

    /** 定向赠红包
     * @param $shop_id 店铺Id
     * @param $mobile 需要发放红包的用户手机号
     * @param $coupon_template 定向赠红包的模板信息
     * @return mixed
     */
    public function present_coupon($shop_id, $mobile, $coupon_template)
    {
        return $this->client->call("eleme.activity.coupon.presentCoupon", array("shopId" => $shop_id, "mobile" => $mobile, "couponTemplate" => $coupon_template));
    }

    /** 分页查询店铺红包活动信息
     * @param $shop_id 店铺Id
     * @param $coupon_activity_type 红包活动类型
     * @param $activity_status 活动状态
     * @param $page_no 页码（第几页）
     * @param $page_size 每页数量
     * @return mixed
     */
    public function query_coupon_activities($shop_id, $coupon_activity_type, $activity_status, $page_no, $page_size)
    {
        return $this->client->call("eleme.activity.coupon.queryCouponActivities", array("shopId" => $shop_id, "couponActivityType" => $coupon_activity_type, "activityStatus" => $activity_status, "pageNo" => $page_no, "pageSize" => $page_size));
    }

    /** 分页查询店铺红包领取详情
     * @param $shop_id 店铺Id
     * @param $coupon_activity_id 红包活动Id
     * @param $coupon_status 红包状态
     * @param $page_no 页码（第几页）
     * @param $page_size 每页数量
     * @return mixed
     */
    public function query_received_coupon_details($shop_id, $coupon_activity_id, $coupon_status, $page_no, $page_size)
    {
        return $this->client->call("eleme.activity.coupon.queryReceivedCouponDetails", array("shopId" => $shop_id, "couponActivityId" => $coupon_activity_id, "couponStatus" => $coupon_status, "pageNo" => $page_no, "pageSize" => $page_size));
    }

    /** 托管单店红包服务
     * @param $shop_ids 餐厅id列表,长度不能超过20
     * @param $hosted_type 红包服务业务类型,暂只支持超级会员,"SUPER_VIP"
     * @param $discounts 扣减额,请设置在[4,15]元,小数点后最多1位
     * @return mixed
     */
    public function host_shops($shop_ids, $hosted_type, $discounts)
    {
        return $this->client->call("eleme.activity.coupon.hostShops", array("shopIds" => $shop_ids, "hostedType" => $hosted_type, "discounts" => $discounts));
    }

    /** 查询红包服务托管情况
     * @param $shop_ids 餐厅id列表,长度不能超过20
     * @param $hosted_type 红包服务业务类型,暂只支持超级会员,"SUPER_VIP"
     * @return mixed
     */
    public function query_host_info($shop_ids, $hosted_type)
    {
        return $this->client->call("eleme.activity.coupon.queryHostInfo", array("shopIds" => $shop_ids, "hostedType" => $hosted_type));
    }

    /** 取消托管单店红包服务
     * @param $shop_ids 餐厅id列表,长度不能超过20
     * @param $hosted_type 红包服务业务类型,暂只支持超级会员,"SUPER_VIP"
     * @return mixed
     */
    public function unhost_shops($shop_ids, $hosted_type)
    {
        return $this->client->call("eleme.activity.coupon.unhostShops", array("shopIds" => $shop_ids, "hostedType" => $hosted_type));
    }

    /** 更改单店红包服务托管方式
     * @param $shop_id 店铺Id
     * @param $hosted_type 红包服务业务类型,暂只支持超级会员,"SUPER_VIP"
     * @param $o_activity_service_details 服务内容
     * @return mixed
     */
    public function rehost_shop($shop_id, $hosted_type, $o_activity_service_details)
    {
        return $this->client->call("eleme.activity.coupon.rehostShop", array("shopId" => $shop_id, "hostedType" => $hosted_type, "oActivityServiceDetails" => $o_activity_service_details));
    }

    /** 通过店铺_id查询该店铺被邀约的美食活动
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function query_invited_food_activities($shop_id)
    {
        return $this->client->call("eleme.activity.food.queryInvitedFoodActivities", array("shopId" => $shop_id));
    }

    /** 报名美食活动
     * @param $activity_id 活动Id
     * @param $activity_apply_info 活动报名信息
     * @return mixed
     */
    public function apply_food_activity($activity_id, $activity_apply_info)
    {
        return $this->client->call("eleme.activity.food.applyFoodActivity", array("activityId" => $activity_id, "activityApplyInfo" => $activity_apply_info));
    }

    /** 通过店铺_id和活动_id分页查询店铺已报名的美食活动
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $page_no 页码
     * @param $page_size 每页数量
     * @return mixed
     */
    public function query_food_activities($activity_id, $shop_id, $page_no, $page_size)
    {
        return $this->client->call("eleme.activity.food.queryFoodActivities", array("activityId" => $activity_id, "shopId" => $shop_id, "pageNo" => $page_no, "pageSize" => $page_size));
    }

    /** 修改美食活动的菜品库存
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $item_id 菜品Id
     * @param $stock 库存
     * @return mixed
     */
    public function update_food_activity_item_stock($activity_id, $shop_id, $item_id, $stock)
    {
        return $this->client->call("eleme.activity.food.updateFoodActivityItemStock", array("activityId" => $activity_id, "shopId" => $shop_id, "itemId" => $item_id, "stock" => $stock));
    }

    /** 取消参与了美食活动的菜品
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $item_id 菜品Id
     * @return mixed
     */
    public function offline_food_activity_item($activity_id, $shop_id, $item_id)
    {
        return $this->client->call("eleme.activity.food.offlineFoodActivityItem", array("activityId" => $activity_id, "shopId" => $shop_id, "itemId" => $item_id));
    }

    /** 作废店铺与美食活动的关联关系
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function unbind_food_activity($activity_id, $shop_id)
    {
        return $this->client->call("eleme.activity.food.unbindFoodActivity", array("activityId" => $activity_id, "shopId" => $shop_id));
    }

    /** 查询店铺邀约活动信息
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function get_invited_activity_infos($shop_id)
    {
        return $this->client->call("eleme.activity.flash.getInvitedActivityInfos", array("shopId" => $shop_id));
    }

    /** 报名限量抢购活动
     * @param $activity_id 活动Id
     * @param $activity_apply_info 活动报名信息
     * @return mixed
     */
    public function apply_flash_activity($activity_id, $activity_apply_info)
    {
        return $this->client->call("eleme.activity.flash.applyFlashActivity", array("activityId" => $activity_id, "activityApplyInfo" => $activity_apply_info));
    }

    /** 通过店铺_id和活动_id分页查询报名详情
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $page_no 页码
     * @param $page_size 每页数量
     * @return mixed
     */
    public function get_activity_apply_infos($activity_id, $shop_id, $page_no, $page_size)
    {
        return $this->client->call("eleme.activity.flash.getActivityApplyInfos", array("activityId" => $activity_id, "shopId" => $shop_id, "pageNo" => $page_no, "pageSize" => $page_size));
    }

    /** 修改活动菜品库存
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $item_id 菜品Id
     * @param $stock 库存
     * @return mixed
     */
    public function update_activity_item_stock($activity_id, $shop_id, $item_id, $stock)
    {
        return $this->client->call("eleme.activity.flash.updateActivityItemStock", array("activityId" => $activity_id, "shopId" => $shop_id, "itemId" => $item_id, "stock" => $stock));
    }

    /** 取消活动菜品
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @param $item_id 菜品Id
     * @return mixed
     */
    public function offline_flash_activity_item($activity_id, $shop_id, $item_id)
    {
        return $this->client->call("eleme.activity.flash.offlineFlashActivityItem", array("activityId" => $activity_id, "shopId" => $shop_id, "itemId" => $item_id));
    }

    /** 作废店铺与活动的关联关系
     * @param $activity_id 活动Id
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function invalid_shop_activity($activity_id, $shop_id)
    {
        return $this->client->call("eleme.activity.flash.invalidShopActivity", array("activityId" => $activity_id, "shopId" => $shop_id));
    }

}