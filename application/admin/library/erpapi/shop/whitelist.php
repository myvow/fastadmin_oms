<?php
/**
 * @author ykm 2016-04-15
 * @describe 平台接口白名单
 */
class erpapi_shop_whitelist {
    private $whiteList;

    public function __construct(){
        $this->whiteList = array(
                'ecos.b2c'   => $this->ecosb2c,
                'bbc'        => $this->bbc,
                '360buy'     => $this->jingdong,
                'jd'         => $this->jd,
                'taobao'     => $this->taobao,
                'espier.yyk' => $this->yyk,
                'espier.caodong' => $this->caodong,
        );
    }

    public function getWhiteList($nodeType) {
        return $this->whiteList[$nodeType] ? array_merge($this->whiteList[$nodeType], $this->public_api) : $this->public_api;
    }

    #平台共有接口
    private $public_api = array(
        SHOP_LOGISTICS_PUB,
        SHOP_LOGISTICS_BIND,
        SHOP_TRADE_FULLINFO_RPC,
        SHOP_FENXIAO_TRADE_FULLINFO_RPC,
        SHOP_IFRAME_TRADE_EDIT_RPC,
        SHOP_GET_TRADES_SOLD_RPC,
        SHOP_LOGISTICS_SUBSCRIBE
    );

    private $public_b2c = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_PAYMENT_STATUS_RPC,
        SHOP_UPDATE_REFUND_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_PAY_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
    );

    /**
     * EC-STORE RPC服务接口名列表
     * @access private
     */
    private $ecosb2c = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_PAYMENT_STATUS_RPC,
        SHOP_UPDATE_REFUND_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_PAY_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
        SHOP_GET_ITEMS_ALL_RPC,// 获取前端商品
        SHOP_GET_ITEMS_LIST_RPC,// 通过IID获取多个前端商品
        //SHOP_UPDATE_ITEM_APPROVE_STATUS_RPC,// 单个商品上下架更新
        //SHOP_UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,批量上下架
        SHOP_ITEM_SKU_GET,// 单拉商品SKU
        SHOP_ITEM_GET,// 通过IID获取单个商品
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_REFUSE_REFUND,
    );

    /**
     * 店掌柜 RPC服务接口名列表
     * @access private
     */
    private $ecosdzg = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_PAYMENT_STATUS_RPC,
        SHOP_UPDATE_REFUND_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_PAY_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
    );

    /**
     * SHOPEX485 RPC服务接口名列表
     * @access private
     */
    private $shopexb2c = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        //SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
    );

    /**
     * 当当 RPC服务接口名列表
     * @access private
     */
    private $dangdang = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_GET_DANGDANG_SHOP_CATEGORYLIST,
        SHOP_UPDATE_DANGDANG_QUANTITY_LIST_RPC,
    );

    /**
     * 一号店 RPC服务接口名列表
     * @access private
     */
    private $yihaodian = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_CHECK_REFUND_GOOD,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_REFUSE_RETURN_GOOD,
        SHOP_GET_TRADE_INVOICE_RPC,
    );

    /**
     * 淘宝 RPC服务接口名列表
     * @access private
     */
    private $taobao = array(
        SHOP_LOGISTICS_ONLINE_SEND, #在线下单
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_WLB_ORDER_JZPARTNER_QUERY,
        SHOP_WLB_ORDER_JZWITHINS_CONSIGN,
        SHOP_TMC_MESSAGE_PRODUCE,
        SHOP_LOGISTICS_DUMMY_SEND,
        SHOP_LOGISTICS_ADDRESS_SEARCH,
        SHOP_BILL_BOOK_BILL_GET,
        SHOP_BILL_BILL_GET,
        SHOP_USER_TRADE_SEARCH,
        SHOP_TOPATS_RESULT_GET,
        SHOP_TOPATS_USER_ACCOUNTREPORT_GET,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_REFUSE_REFUND,
        SHOP_AGREE_RETURN_I_GOOD_TMALL,
        SHOP_REFUSE_RETURN_I_GOOD_TMALL,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_GET_ITEMS_ALL_RPC,
        SHOP_GET_ITEMS_LIST_RPC,
        SHOP_GET_FENXIAO_PRODUCTS,
        SHOP_ITEM_GET,
        SHOP_UPDATE_FENXIAO_PRODUCT,
        SHOP_ITEM_SKU_GET,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_RPC,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,
        SHOP_GET_REFUND_MESSAGE,
        SHOP_GET_REFUND_I_MESSAGE_TMALL,
        SHOP_ADD_REFUND_MESSAGE,
        SHOP_REFUND_GOOD_RETURN_CHECK,
        SHOP_GET_TRADE_REFUND_RPC,
        SHOP_GET_TRADE_REFUND_I_RPC,
        SHOP_REFUNSE_REFUND_I_TMALL,
        SHOP_AGREE_REFUND_I_TMALL,
        SHOP_GET_CLOUD_STACK_PRINT_TAG,
        SHOP_WLB_ORDER_JZ_QUERY,
        SHOP_WLB_ORDER_JZ_CONSIGN,
        SHOP_GET_ACCOUNTREPORT,
        STORE_AG_SENDGOODS_CANCEL,
        STORE_AG_LOGISTICS_WAREHOUSE_UPDATE,
        STORE_CN_RULE,
        STORE_CN_SMARTDELIVERY,
        SHOP_WLB_THREEPL_OFFLINE_SEND,
        SHOP_WLB_THREEPL_RESOUCE_GET,
        SHOP_EXCHANGE_RETURNGOODS_AGREE,
        SHOP_EXCHANGE_RETURNGOODS_REFUSE,
        SHOP_EXCHANGE_REFUSEREASON_GET,
        SHOP_EXCHANGE_MESSAGE_GET,
        SHOP_EXCHANGE_MESSAGE_ADD,
        SHOP_EXCHANGE_CONSIGNGOODS,
        SHOP_REFUSE_CHANGE_I_GOOD_TMALL,
        SHOP_AGREE_CHANGE_I_GOOD_TMALL,
        SHOP_EXCHANGE_GET,
        SHOP_RDC_ORDERMSG_UPDATE,
        STORE_CN_WAYBILL_II_SEARCH,
        LOGISTICS_SERVICE_AREAS_ALL_GET,
    );

    /**
     * 拍拍 RPC服务接口名列表
     * @access private
     */
    private $paipai = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_GET_ITEMS_ALL_RPC,
        SHOP_ITEM_GET,
        SHOP_ITEM_SKU_GET,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_RPC,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,
    );

    /**
     * qq网购 RPC服务接口名列表
     * @access private
     */
    private $qqbuy = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    /**
     * SHOPEX B2B RPC服务接口名列表
     * @access private
     */
    private $shopexb2b = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
    );


    /**
     * 京东 RPC服务接口名列表
     * @access private
     */
    private $jingdong = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_CHECK_REFUND_GOOD,
        SHOP_GET_ITEMS_ALL_RPC,
        SHOP_GET_ITEMS_LIST_RPC,
        SHOP_ITEM_GET,
        SHOP_ITEM_SKU_GET,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_RPC,
        SHOP_UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,
        SHOP_GET_ITEMS_VALID_RPC,
        SHOP_ITEM_I_GET,
        SHOP_ITEM_SKU_I_GET,
        SHOP_LOGISTICS_ADDRESS_SEARCH,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_REFUSE_RETURN_GOOD,
        SHOP_ASC_AUDIT_REASON_GET,
    );

    /**
     * 京东供应商平台 RPC服务接口名列表
     * @access private
     */
    private $jd = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_REFUND_CHECK,
        SHOP_OUT_BRANCH,
    );
    
    /**
     * 亚马逊 RPC服务接口名列表
     * @access private
     */
    private $amazon = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    /**
     * 凡客 RPC服务接口名列表
     * @access private
     */
    private $vjia = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_TRADE_OUTSTORAGE,
        SHOP_LOGISTICS_CONSIGN_RESEND,
        SHOP_LOGISTICS_RESEND_CONFIRM,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_GET_TRADE_INVOICE_RPC,
    );

    /**
     * 阿里巴巴 RPC服务接口名列表
     * @access private
     */
    private $alibaba = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_ITEM_GET,
    );

    /**
     * 苏宁 RPC服务接口名列表
     * @access private
     */
    private $suning = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ITEM_GET,
        SHOP_GET_ITEMS_CUSTOM,
        STORE_LOGISTICS_DUMMY_SEND,
    );
    /**
     * 银泰 RPC服务接口列表
     * @access private
     */
    private $yintai = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_GET_ITEMS_CUSTOM,
    );
    #工行RPC服务接口
    private $icbc = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #蘑菇街RPC服务接口
    private $mogujie = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #国美RPC服务接口
    private $gome= array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #微信RPC服务接口
    private $wx= array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ITEM_GET,
    );
    #建设银行RPC服务接口
    private $ccb = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #meilishuoRPC服务接口
    private $meilishuo = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_MEILISHUO_REFUND_GOOD_RETURN_AGREE,
    );
    #飞牛RPC服务接口
    private $feiniu = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #有赞RPC服务接口
    private $youzan = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    #卷皮RPC服务接口
    private $juanpi = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );
    #蜜芽宝贝RPC服务接口
    private $mia = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    /**
     * 店掌柜 RPC服务接口名列表
     * @access private
     */
    private $bbc = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_REFUSE_REFUND,
        SHOP_ADD_REFUND_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_GET_ITEMS_ALL_RPC,
        SHOP_GET_ITEMS_LIST_RPC,
        SHOP_ITEM_GET,
        SHOP_ITEM_SKU_GET,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
    );
    private $beibei = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC
    );
    private $wdwd = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC
    );
    #唯品会RPC服务接口
    private $vop = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_COMMONS_VOP_JIT,
        SHOP_GET_ORDER_STATUS,
        SHOP_PRINT_THIRD_BILL,
        SHOP_GET_DLY_INFO,
    );
    private $mengdian = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_LOGISTICS_DUMMY_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC
    );
    #ecshop
    private $ecshop = array(
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND
    );
    private $zhe800 = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    private $kaola = array(
            SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
            SHOP_LOGISTICS_OFFLINE_SEND,
            SHOP_AGREE_REFUND,
            SHOP_REFUSE_REFUND,
            SHOP_AGREE_REFUNDGOODS,
            SHOP_REFUSE_REFUNDGOODS,
    );

    private $pinduoduo = array(
            SHOP_LOGISTICS_OFFLINE_SEND,
            SHOP_GET_ORDER_STATUS,
    );

   private $weimob = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    /**
     * mls (美丽说2)RPC服务接口
     * @var array
     */
    private $mls = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    private $mgj = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    private $shopex_fy = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_PAYMENT_STATUS_RPC,
        SHOP_UPDATE_REFUND_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_PAY_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
    );

    private $shopex_penkrwd = array(
        SHOP_TRADE_SHIPPING_ADD,
        SHOP_TRADE_SHIPPING_STATUS_UPDATE,
        SHOP_TRADE_SHIPPING_UPDATE,
        SHOP_PAYMETHOD_RPC,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_PAYMENT_RPC,
        SHOP_ADD_AFTERSALE_RPC,
        SHOP_UPDATE_AFTERSALE_STATUS_RPC,
        SHOP_ADD_REFUND_RPC,
        SHOP_ADD_RESHIP_RPC,
        SHOP_UPDATE_RESHIP_STATUS_RPC,
        SHOP_UPDATE_PAYMENT_STATUS_RPC,
        SHOP_UPDATE_REFUND_STATUS_RPC,
        SHOP_UPDATE_TRADE_RPC,
        SHOP_UPDATE_TRADE_STATUS_RPC,
        SHOP_UPDATE_TRADE_TAX_RPC,
        SHOP_UPDATE_TRADE_SHIP_STATUS_RPC,
        SHOP_UPDATE_TRADE_PAY_STATUS_RPC,
        SHOP_UPDATE_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_MEMO_RPC,
        SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,
        SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,
        SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,
    );

    private $cmb = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    private $renrendian = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    #好食期
    private $haoshiqi = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    #微店服务接口
    private $weidian = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_LOGISTICS_DUMMY_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    //蜂潮服务接口
    private $eyee = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_LOGISTICS_DUMMY_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    //云集
    private $yunji = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );
    
    //小红书
    private $xiaohongshu = array(
            SHOP_LOGISTICS_OFFLINE_SEND,
            SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    
    //顺逛
    private $shunguang = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ADD_REFUND_RPC,
    );

    //聪明购
    private $congminggou = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_ADD_REFUND_RPC,
    );

    //格家网络
    private $gegejia = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
    );

    //小店
    private $xiaodian = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );

    //爱库存
    private $aikucun = array(
        SHOP_LOGISTICS_OFFLINE_SEND
    );

    //微盟微商城
    private $weimobv = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_REFUSE_RETURN_GOOD,
        SHOP_RETURN_GOOD_CONFIRM
    );
    
    // 环球捕手 by mxc
    private $gs = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
    );
    
    private $kaola4zy = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_RDC_ORDERMSG_UPDATE
    );

    //鱼塘
    private $yutang = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND
    );

    /**
     * 苏宁自营 RPC服务接口名列表
     * @access private
     */
    private $suning4zy = array(
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_ITEM_GET,
        SHOP_GET_ITEMS_CUSTOM,
        SHOP_LOGISTICS_ADDRESS_SEARCH,
        SHOP_REFUSE_REFUND,
        SHOP_AGREE_REFUND,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_REFUSE_RETURN_GOOD
    );
    
    private $mingrong = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_EXCHANGE_GET,
        SHOP_EXCHANGE_CONSIGNGOODS,
        SHOP_AGREE_REFUND,
        SHOP_REFUSE_REFUND,
        SHOP_AGREE_RETURN_GOOD,
        SHOP_AGREE_CHANGE_I_GOOD_TMALL,
        SHOP_REFUSE_CHANGE_I_GOOD_TMALL,
        SHOP_REFUSE_RETURN_GOOD
    );

    // 虎扑
    private $hupu = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_GET_ORDER_STATUS,
    );

    private $luban = array(
        SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC,
        SHOP_LOGISTICS_OFFLINE_SEND,
        SHOP_GET_ORDER_STATUS,
    );
}
