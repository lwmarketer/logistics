<?php


namespace Lovevox\Logistics\Helper;


use Magento\Framework\View\Asset\NotationResolver\Variable;

class Data
{
    const PACKAGE_STATUS_NOT_FOUND = 0;//查询不到
    const PACKAGE_STATUS_IN_TRANSIT = 10;//运输途中
    const PACKAGE_STATUS_EXPIRED = 20;//运输过久
    const PACKAGE_STATUS_PICK_UP = 30;//到达待取
    const PACKAGE_STATUS_UNDELIVERED = 35;//投递失败
    const PACKAGE_STATUS_DELIVERED = 40;//成功签收
    const PACKAGE_STATUS_ALERT = 50;//可能异常

    const TRACK_STATUS_UNABLE_TO_TRACK = 0;//无法识别
    const TRACK_STATUS_NORMAL_TRACK = 1;//正常查有信息
    const TRACK_STATUS_NOT_FOUND = 2;//尚无信息
    const TRACK_STATUS_WEB_ERROR = 10;//网站错误
    const TRACK_STATUS_PROCESS_ERROR = 11;//处理错误
    const TRACK_STATUS_SERVICE_ERROR = 12;//查询错误
    const TRACK_STATUS_WEB_ERROR_CACHE = 20;//网站错误，使用缓存
    const TRACK_STATUS_PROCESS_ERROR_CACHE = 21;//处理错误，使用缓存
    const TRACK_STATUS_SERVICE_ERROR_CACHE = 22;//查询错误，使用缓存

    protected $page_number = 1;

    protected $page_size = 40;

    protected $deploymentConfig;
    protected $objectManager;
    protected $logger;
    public $storeManager;

    public $logisticsCountryCollectionFactory;
    public $logisticsCarrierCollectionFactory;
    public $logisticsHistoryCollectionFactory;
    public $logisticsCountryFactory;
    public $logisticsCarrierFactory;
    public $logisticsHistoryFactory;

    protected $headers = [];

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Lovevox\Logistics\Model\LogisticsCountryFactory $logisticsCountryFactory,
        \Lovevox\Logistics\Model\LogisticsCarrierFactory $logisticsCarrierFactory,
        \Lovevox\Logistics\Model\LogisticsHistoryFactory $logisticsHistoryFactory,
        \Lovevox\Logistics\Model\ResourceModel\Country\LogisticsCountryCollectionFactory $logisticsCountryCollectionFactory,
        \Lovevox\Logistics\Model\ResourceModel\Carrier\LogisticsCarrierCollectionFactory $logisticsCarrierCollectionFactory,
        \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollectionFactory $logisticsHistoryCollectionFactory
    )
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->deploymentConfig = $deploymentConfig->get('17track');
        $this->objectManager = $objectManager;
        $this->logisticsCountryCollectionFactory = $logisticsCountryCollectionFactory;
        $this->logisticsCarrierCollectionFactory = $logisticsCarrierCollectionFactory;
        $this->logisticsHistoryCollectionFactory = $logisticsHistoryCollectionFactory;
        $this->logisticsCountryFactory = $logisticsCountryFactory;
        $this->logisticsCarrierFactory = $logisticsCarrierFactory;
        $this->logisticsHistoryFactory = $logisticsHistoryFactory;
        $this->headers = ['17token:' . $this->deploymentConfig['track_api_secret'], 'Content-type: application/json'];
    }

    /**
     * @return int
     */
    public function getPageNumber(): int
    {
        return $this->page_number;
    }

    /**
     * @param int $page_number
     */
    public function setPageNumber(int $page_number): void
    {
        $this->page_number = $page_number;
    }


    /**
     * 同步国家编码
     * @return int
     */
    public function updateCountry()
    {
        $this->logger->info("start exec date=========>updateCountry:" . date('Y-m-d H:i:s'));
        $updateCnt = 0;
        $result = $this->curlPostJson($this->deploymentConfig['county_api_url']);
        if ($result) {
            /** @var \Lovevox\Logistics\Model\ResourceModel\Country\LogisticsCountryCollection $collection */
            $collection = $this->logisticsCountryCollectionFactory->create();
            $data = json_decode($result, true);
            foreach ($data as $datum) {
                try {
                    /** @var \Lovevox\Logistics\Model\LogisticsCountry $item */
                    $item = $collection->getItemByColumnValue('code', $datum['key']);
                    if ($item) {
                        if ($item->getData('name') != $datum['_name']) {
                            $item->setData('name', $datum['_name']);
                            $item->save();
                            $updateCnt++;
                        }
                    } else {
                        $new = $this->logisticsCountryFactory->create();
                        $new->setData('code', $datum['key']);
                        $new->setData('name', $datum['_name']);
                        $new->save();
                        $updateCnt++;
                    }
                } catch (\Exception $exception) {
                    $this->logger->info('Update Logistics Country ==========>info:' . $datum['key'] . ':' . $datum['_name']);
                    $this->logger->info('Update Logistics Country ==========>error:' . $exception->getTraceAsString());
                    continue;
                }
            }
        }
        $this->logger->info("end exec date=========>updateCountry:" . date('Y-m-d H:i:s'));
        return $updateCnt;
    }

    /**
     * 同步运输商编号数据
     * @return int
     */
    public function updateCarrier()
    {
        $this->logger->info("start exec date=========>updateCarrier:" . date('Y-m-d H:i:s'));
        $updateCnt = 0;
        $result = $this->curlPostJson($this->deploymentConfig['carrier_api_url']);
        if ($result) {
            /** @var \Lovevox\Logistics\Model\ResourceModel\Carrier\LogisticsCarrierCollection $collection */
            $collection = $this->logisticsCarrierCollectionFactory->create();
            $data = json_decode($result, true);

            foreach ($data as $datum) {
                try {
                    /** @var \Lovevox\Logistics\Model\LogisticsCarrier $item */
                    $item = $collection->getItemByColumnValue('code', $datum['key']);
                    if ($item) {
                        $isUpdated = false;
                        if ($item->getData('can_track') != $datum['_canTrack']) {
                            $item->setData('can_track', $datum['_canTrack']);
                        }
                        if ($item->getData('country') != $datum['_country']) {
                            $item->setData('country', $datum['_country']);
                        }
                        if ($item->getData('url') != $datum['_url']) {
                            $item->setData('url', $datum['_url']);
                        }
                        if ($item->getData('name') != $datum['_name']) {
                            $item->setData('name', $datum['_name']);
                        }
                        if ($isUpdated) {
                            $item->save();
                            $updateCnt++;
                        }
                    } else {
                        $new = $this->logisticsCarrierFactory->create();
                        $new->setData('code', $datum['key']);
                        $new->setData('can_track', $datum['_canTrack']);
                        $new->setData('country', $datum['_country']);
                        $new->setData('url', $datum['_url']);
                        $new->setData('name', $datum['_name']);
                        $new->save();
                        $updateCnt++;
                    }
                } catch (\Exception $exception) {
                    $this->logger->info('Update Logistics Carrier ==========>info:' . json_encode($datum));
                    $this->logger->info('Update Logistics Carrier ==========>error:' . $exception->getTraceAsString());
                    continue;
                }
            }
        }
        $this->logger->info("end exec date=========>updateCarrier:" . date('Y-m-d H:i:s'));
        return $updateCnt;
    }

    /**
     * @param null $order_id
     */
    public function updateOrderTrack($order_id = null)
    {
        var_dump("start exec date=========>updateOrderTrack:" . date('Y-m-d H:i:s'));
        $this->logger->info("start exec date=========>updateOrderTrack:" . date('Y-m-d H:i:s'));

        $success = $is_break = 0;
        $carrierCollection = $this->logisticsCarrierCollectionFactory->create();

        while (true) {
            //连续5次查询数据为空时终止操作
            if ($is_break == 1) {
                break;
            }
            try {
                /** @var \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollection $collection */
                $collection = $this->logisticsHistoryCollectionFactory->create();
                $collection->addFieldToFilter('status', ['neq' => 40]);
                $collection->addFieldToFilter('update_date', [['neq' => date('Y-m-d')], ['null' => true]]);
                if (!is_null($order_id)) {
                    $collection->addOrderFilter($order_id);
                }
                $collection->getSelect()->limit($this->page_size, ($this->getPageNumber() - 1) * $this->page_size);
                var_dump($collection->count() . ':' . $collection->getSelect()->__toString());
                $order_track_data = $carriers = $data = [];
                if ($collection->count()) {
                    $this->getCarriersByOrder($carriers, $collection, $carrierCollection);
                    foreach ($collection->getItems() as $item) {
                        if (!empty($item['track_number']) && isset($carriers[$item['express']])) {
                            $curCarrier = $carriers[$item['express']];
                            $data[] = ['number' => $item['track_number'], 'carrier' => $curCarrier];
                            if (!empty($curCarrier)) {
                                $order_track_data[$item['track_number']] = $curCarrier;
                            }
                        }
                    }
                    $json_result = $this->curlPostJson($this->deploymentConfig['17track_api_url'] . 'register', $data, $this->headers);

                    $result = json_decode($json_result, true);

                    if ($result['code'] === 0) {
                        $acceptedArray = $result['data']['accepted'];
                        //修复运输商编号不准确的运单号
                        $this->updateCarrierByTrack($acceptedArray, $order_track_data);
                    }
                    //更新物流信息
                    $this->updateTrackByOrder($data, $collection, $success);
                }
                if ($success > 0 && $collection->count()) {
                    $is_break = 0;
                } else {
                    $is_break++;
                }
            } catch (\Exception $exception) {
                $this->logger->info("end exec error=========>updateOrderTrack:" . $exception->getTraceAsString());
                return $success;
            }
        }
        var_dump("end exec date=========>updateOrderTrack:" . date('Y-m-d H:i:s'));
        $this->logger->info("end exec date=========>updateOrderTrack:" . date('Y-m-d H:i:s'));
        return $success;
    }

    /**
     * 修复运输商编号不准确的运单号
     * @param $acceptedArray
     * @param $order_track_data
     */
    protected function updateCarrierByTrack($acceptedArray, $order_track_data)
    {
//        var_dump('updateCarrierByTrack');
        $data = [];
        foreach ($acceptedArray as $accepted) {
            if (isset($order_track_data[$accepted['number']])) {
                if ($order_track_data[$accepted['number']] != $accepted['carrier']) {
                    $data[] = ['number' => $accepted['number'], 'carrier_old' => $order_track_data[$accepted['number']], 'carrier_new' => $accepted['carrier']];
                }
            }
        }
        if (count($data) > 0) {
            $result = $this->curlPostJson($this->deploymentConfig['17track_api_url'] . 'changecarrier', $data, $this->headers);
//            var_dump($result);
        }
    }

    /**
     * 更新物流信息
     * @param $data
     * @param \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollection $collection
     * @param $success
     * @throws \Exception
     */
    protected function updateTrackByOrder($data, $collection, &$success)
    {
        if (count($data) > 0) {
            $track_json_result = $this->curlPostJson($this->deploymentConfig['17track_api_url'] . 'gettrackinfo', $data, $this->headers);

            $track_result = json_decode($track_json_result, true);
            if ($track_result['code'] === 0 && isset($track_result['data']['accepted'])) {
                $list = $track_result['data']['accepted'];
                foreach ($list as $item) {
                    try {
                        /** @var \Lovevox\Logistics\Model\LogisticsHistory $info */
                        $info = $collection->getItemByColumnValue('track_number', $item['number']);
                        if ($info) {
                            var_dump('updateTrackByOrder===============>info:' . $item['number'] . ':' . $item['track']['e']);
                            $info->setData('status', $item['track']['e']);
                            $info->setData('track_content', json_encode($item['track']));
                            $info->setData('update_date', date('Y-m-d'));
                            $info->save();
                            $success++;
                        }
                    } catch (\Exception $exception) {
                        $this->logger->info('updateTrackByOrder ================>error:' . json_encode($item));
                        $this->logger->info('updateTrackByOrder ================>error:' . $exception->getTraceAsString());
                        continue;
                    }
                }

                $list = $track_result['data']['rejected'];
                foreach ($list as $item) {
                    try {
                        /** @var \Lovevox\Logistics\Model\LogisticsHistory $info */
                        $info = $collection->getItemByColumnValue('track_number', $item['number']);
                        if ($info) {
                            $info->setData('status', 0);
                            $info->setData('track_content', json_encode($item['error']));
                            $info->setData('update_date', date('Y-m-d'));
                            $info->save();
                            $success++;
                        }
                    } catch (\Exception $exception) {
                        $this->logger->info('updateTrackByOrder ================>error:' . json_encode($item));
                        $this->logger->info('updateTrackByOrder ================>error:' . $exception->getTraceAsString());
                        continue;
                    }
                }
            }
        }
    }

    /**
     * @param $carriers
     * @param \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollection $collection
     * @param $tempCarrierCollection
     */
    protected function getCarriersByOrder(&$carriers, $collection, $tempCarrierCollection)
    {
        try {
            $expressArray = $collection->getColumnValues('express');
            $expressArray = array_unique($expressArray);
            foreach ($expressArray as $express) {
                foreach ($tempCarrierCollection as $carrier) {
                    //优先全词匹配，其次模糊匹配
                    if ($carrier->getData('name') == $express) {
                        $carriers[$express] = $carrier->getData('code');
                        break;
                    } else if (strrpos($carrier->getData('name'), $express) !== false) {
                        $carriers[$express] = $carrier->getData('code');
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->info('getCarriersByOrder ================>error:' . $exception->getTraceAsString());
        }
    }

    /**
     * 请求函数
     * @param $url
     * @param bool $data
     * @param bool $headers
     * @return bool|string
     */
    private function curlPostJson($url, $data = false, $headers = false)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($headers) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $result = curl_exec($ch);

            curl_close($ch);
        } catch (\Exception $exception) {
            var_dump($exception->getTraceAsString());
            $this->logger->info('curlPostJson=========>url:' . $url);
            $this->logger->info('curlPostJson=========>data:' . json_encode($data));
            $this->logger->info('curlPostJson=========>headers:' . json_encode($headers));
            $this->logger->info('curlPostJson=========>error:' . $exception->getTraceAsString());
            return false;
        }
        return $result;
    }

    private function diff()
    {
        $str1 = 'AW210330597,DHL,3848817825###AW210322557,DHL,1641763734###201208135,UPS,1ZA28893YW06254165###201208153,DHL,4556606202###201208152,DHL,9610536154###201209154,DHL,8339331420###AW201211162,DHL,4178623400###AW201212164,DHL,2469310421###AW201214168,DHL,4178623411###AW201212166,DHL,7198745256###AW201221190,DHL,8280191496###AW201215171,DHL,1511245982###AW201225210,DHL,6330905873###AW201230220,DHL,8143352206###AW201230221,DHL,5028160163###AW210101223,DHL,2535452371###AW210104225,DHL,2709488611###AW210106229,DHL,8731058965###AW210108231,DHL,7074938496###AW210109236,DHL,2763720315###AW210109238,DHL,2543775511###AW210110241,DHL,5057154491###AW210112243,DHL,2543764462###AW210113249,DHL,6092823870###AW210114253,DHL,5901143076###AW210118260,DHL,6309454060###AW210118261,China Post,LZ628487726CN###AW210119267,DHL,6345002576###AW210125309,DHL,5825565885###AW210127322,DHL,3251309401###AW210128328,DHL,1032894052###AW210128329,DHL,2322339003###AW210128334,DHL,1032613503###AW210129338,DHL,7691163351###AW210129341,DHL,1032608706###AW210131346,DHL,9872553681###AW210131348,DHL,4996272455###AW210131354,DHL,9892855033###AW210201366,DHL,5272321972###AW210203374,DHL,6372822116###AW210204378,DHL,9892854296###AW210206387,DHL,5377849422###AW210206390,DHL,3439213610###AW210207394,DHL,5377890280###AW210210405,DHL,1293100896###AW210210406,DHL,1689159275###AW210212411,DHL,8371661583###AW210213416,DHL,6289863786###AW210214417,DHL,1689061555###AW210214418,DHL,6285422430###AW210214421,DHL,1619442230###AW210214423,DHL,2234217786###AW210216427,DHL,5014137030###AW210216432,DHL,2234218022###AW210217433,DHL,1293196715###AW210217435,DHL,2442295995###AW210219438,DHL,8375440220###AW210219440,DHL,2442297454###AW210219439,DHL,1804605456###AW210219442,DHL,6456677404###AW210220443,DHL,2214339190###AW210220444,DHL,1293198222###AW210220445,DHL,5536781865###AW210220446,DHL,5272379221###AW210221448,DHL,1630515810###AW210222454,DHL,5377860600###AW210222455,DHL,4724852296###AW210222457,China Post,LY675718255CN###AW210223460,DHL,8371716231###AW210224464,DHL,1293198196###AW210224469,DHL,6980816076###AW210226473,China Post,LY678064827CN###AW210227476,China Post,LY682811605CN###AW210227478,DHL,5611753976###AW210227479,China Post,LY705237904CN###AW210227482,China Post,LY686938650CN###AW210227484,DHL,1689162440###AW210301491,China Post,LY686934406CN###AW210301492,China Post,LY686874486CN###AW210302495,China Post,LY688442917CN###AW210302497,China Post,LY691349951CN###AW210302502,DHL,5014150562###AW210302501,DHL,9153721135###AW210302504,DHL,2605828525###AW210303506,DHL,8599336605###AW210303509,DHL,6738909726###AW210304510,China Post,LY678066638CN###AW210305517,DHL,4965896353###AW210305518,China Post,LY691306545CN###AW210305519,DHL,6289863926###AW210307007,China Post,LY685427717CN###AW210307009,China Post,LY705244984CN###AW210308011,DHL,1802790625###AW210308523,DHL,8963978472###AW210309525,DHL,5870842751###AW210309013,DHL,4886000696###AW210309526,DHL,5870845186###AW210310016,DHL,8393533422###AW210310527,DHL,6289864254###AW210310528,DHL,1560540240###AW210311535,China Post,LY685443003CN###AW210311536,China Post,LZ730298763CN###AW210311024,China Post,LY685446614CN###AW210312026,China Post,LY702635354CN###AW210312027,DHL,5611740945###AW210312537,China Post,LY717596643CN###AW210313028,China Post,LY718822774CN###AW210314540,DHL,1560607263###AW210314035,DHL,7516393544###AW210314036,DHL,4090597453###AW210314038,DHL,1264233390###AW210315040,China Post,LY692587816CN###AW210316546,DHL,1480913700###AW210317049,China Post,LY705879293CN###AW210319549,DHL,3029778920###AW210319064,China Post,LY717598406CN###AW210320066,DHL,4609915365###AW210320069,China Post,LY732514417CN###AW210321072,DHL,7298064152###AW210321076,China Post,LY714008238CN###AW210321077,DHL,6447456374###AW210322079,China Post,LY715772756CN###AW210322556,DHL,1006975255###AW210322557,DHL,6331237404###AW210322081,China Post,LY720259221CN###AW210322558,China Post,LY723904683CN###AW210322082,DHL,2250208995###AW210322084,China Post,LZ782096585CN###AW210323085,China Post,LY723318167CN###AW210323087,China Post,LY726689706CN###AW210323561,DHL,8393409032###AW210324562,DHL,8322658680###AW210324092,DHL,6335528990###AW210325565,China Post,LY724055436CN###AW210325570,DHL,6447752286###AW210325096,DHL,2088504972###AW210325571,China Post,LY725873785CN###AW210325097,DHL,8322808045###AW210325098,DHL,8393200734###AW210326103,DHL,7013583371###AW210327581,China Post,LY729146916CN###AW210327582,DHL,2086643285###AW210327108,China Post,LY732420683CN###AW210327583,DHL,8211976732###AW210327584,China Post,LY727403909CN###AW210328585,China Post,LY738302305CN###AW210328113,China Post,LY719013075CN###AW210328112,China Post,LY734072385CN###AW210328117,China Post,LZ795397924CN###AW210328587,China Post,LY727673092CN###AW210329120,DHL,8574432016###AW210329592,DHL,6920182511###AW210329591,China Post,LZ795401337CN###AW210330598,DHL,5482644101###AW210401128,DHL,8421828225###AW210401131,DHL,2721242252###AW210402603,China Post,LY741835247CN###AW210403608,China Post,LY726072750CN###AW210403609,China Post,LY738295330CN###AW210404613,DHL,1564042491###AW210405615,DHL,1200418542###AW210405142,DHL,1564031431###AW210405616,DHL,3848516232###AW210405617,China Post,LY717332947CN###AW210406619,DHL,8122152345###AW210406620,DHL,1934689374###AW210406622,DHL,9818565702###AW210406623,China Post,LY743433215CN###AW210407150,China Post,LZ784039047CN###AW210407151,DHL,8433871762###AW210407157,DHL,2479423332###AW210407625,DHL,7664218833###AW210408162,China Post,LY748310045CN###AW210408165,China Post,LY734908363CN###AW210408167,DHL,8249795956###AW210409168,China Post,LY738336961CN###AW210409631,DHL,4674079642###AW210411638,DHL,5173213872###AW210411639,DHL,3860943343###AW210412187,DHL,9300141152###AW210412641,China Post,LY766311395CN###AW210412195,DHL,3129420335###AW210413199,China Post,LY750379727CN###AW210413200,DHL,8328849443###AW210413643,DHL,5012649154###AW210413203,DHL,2479509664###AW210416652,DHL,7663895024###AW210416217,DHL,7663935344###AW210417227,DHL,7013342593###AW210417226,DHL,7282644600###AW210417228,DHL,7335688231###AW210418234,DHL,6748617153###AW210419661,DHL,6102524632###AW210419244,DHL,9166099121###AW210419663,DHL,7664063562###AW210420247,DHL,5156452556###AW210420248,DHL,1784149920###AW210421668,China Post,LY769067111CN###AW210421259,DHL,9237973813###AW210422262,DHL,8587781193###AW210422270,China Post,LY770743874CN###AW210423276,DHL,3126669803###AW210424675,DHL,7664218914###AW210424287,DHL,6102536624###AW210424292,China Post,LZ847912090CN###AW210425294,China Post,LY765103618CN###AW210425296,DHL,7788189264###AW210425299,DHL,5737722034###AW210425301,China Post,LY769194398CN###AW210425302,China Post,LZ843042336CN###AW210425303,DHL,1209101132###AW210425679,DHL,5878467755###AW210425678,DHL,2708149905###AW210426308,China Post,LY770719795CN###AW210426309,China Post,LY766220862CN###AW210428325,China Post,LY763200841CN###AW210428687,DHL,6102547673###AW210428327,DHL,2023650495###AW210428331,DHL,1100325730###AW210429335,DHL,6695901472###AW210429690,China Post,LY770744150CN###AW210429339,DHL,3126846730###AW210429340,DHL,9130277111###AW210429346,DHL,7276096881###AW210429348,DHL,9237921475###AW210430696,DHL,8121145314###AW210430355,DHL,1331345304###AW210430356,DHL,1894938183###AW210430358,China Post,LY764569920CN###AW210501700,DHL,8125491054###AW210501701,DHL,7276143685###AW210501702,DHL,3325859375###AW210501360,China Post,LY765084922CN###AW210501703,DHL,2288856776###AW210501705,DHL,4994435854###AW210501365,DHL,3126843193###AW210501366,DHL,5483763316###AW210501367,DHL,1251530615###AW210502371,DHL,1810611865###AW210502381,DHL,5976177771###AW210502383,DHL,3575154520###AW210502384,DHL,9130479341###AW210502385,DHL,5483831205###AW210503392,DHL,8164543881###AW210503394,DHL,2268098453###AW210503711,DHL,3436685851###AW210503712,China Post,LY775565610CN###AW210504715,China Post,LY769129434CN###AW210504407,DHL,4994451033###AW210504406,DHL,3739187734###AW210504411,DHL,2692711851###AW210504414,DHL,5228221121###AW210505416,DHL,7073534381###AW210505417,DHL,2766272224###AW210505723,DHL,7073351261###AW210506427,DHL,1703858881###AW210506429,DHL,2385231612###AW210506725,China Post,LY771977819CN###AW210507443,China Post,LY764791354CN###AW210507442,China Post,LY771943069CN###AW210507730,China Post,LY771794550CN###AW210507445,DHL,6929376974###AW210507450,DHL,2474679465###AW210507455,DHL,9130491263###AW210507456,DHL,2474679966###AW210508458,DHL,4526469953###AW210508733,DHL,7073280130###AW210508469,DHL,9105010504###AW210508470,DHL,1087387910###AW210508734,DHL,7138698210###AW210508474,China Post,LY774446868CN###AW210509480,DHL,3748601253###AW210510483,China Post,LY773249115CN###AW210510482,DHL,2673145016###AW210510484,DHL,3768128512###AW210510485,DHL,9165974963###AW210510742,China Post,LY774682946CN###AW210510746,China Post,LY774578957CN###AW210511493,DHL,1771867241###AW210511499,DHL,3663482196###AW210511752,DHL,3748597392###AW210511751,DHL,6188717986###AW210512753,China Post,LY780638283CN###AW210512513,DHL,1454046064###AW210513515,DHL,1930539822###AW210513518,DHL,9418185206###AW210513521,DHL,4587209734###AW210514523,DHL,7788231194###AW210514763,DHL,8522520204###AW210514525,DHL,2124602885###AW210514526,DHL,4936624361###AW210514765,China Post,LY781914393CN###AW210515767,China Post,LZ870391716CN###AW210515768,DHL,9105409246###AW210515534,DHL,8522374453###AW210515535,DHL,2347622933###AW210515538,DHL,1087734690###AW210515769,DHL,4587284380###AW210515770,China Post,LY781911423CN###AW210516543,China Post,LY781849893CN###AW210516548,DHL,3584304393###AW210516774,DHL,3663165170###AW210517776,China Post,LY785932171CN###AW210517557,DHL,8145510321###AW210517561,DHL,6139622366###AW210518779,DHL,6974980515###AW210518571,DHL,6734091954###AW210518572,China Post,LY790697621CN###AW210518576,DHL,9105314466###AW210518784,DHL,3663553246###AW210518582,China Post,LY785873875CN###AW210518785,China Post,LY787196112CN###AW210518786,DHL,4026732813###AW210518584,DHL,4640432812###AW210519787,DHL,8522394042###AW210519587,China Post,LY790509819CN###AW210519791,DHL,1087741616###AW210519789,China Post,LY787141856CN###AW210519792,DHL,2715393704###AW210519794,DHL,6734108021###AW210519795,DHL,5618209026###AW210519588,DHL,6139669266###AW210520799,DHL,8066781682###AW210520598,DHL,3242048353###AW210520801,China Post,LY789372818CN###AW210520605,DHL,3770001115###AW210521803,DHL,3647479651###AW210521610,DHL,4110666302###AW210522615,DHL,8192656861###AW210522614,China Post,LY790804453CN###AW210522616,DHL,4640332056###AW210522619,China Post,LY798406608CN###AW210522620,DHL,4110594084###AW210523810,DHL,1261551045###AW210523812,DHL,8192789850###AW210523630,DHL,6333036032###AW210524631,China Post,LY790792392CN###AW210524635,DHL,4276222941###AW210524815,DHL,8192662133###AW210524814,DHL,7184675153###AW210524632,DHL,3724928804###AW210524640,DHL,8192707821###AW210524639,DHL,8192781542###AW210524814,DHL,7184700795###AW210525822,DHL,6036690450###AW210525653,DHL,7578584495###AW210526657,DHL,4379608111###AW210526662,China Post,LY793571090CN###AW210526829,DHL,3647559296###AW210526827,China Post,LY789313148CN###AW210527834,DHL,4774034086###AW210527680,DHL,3647287711###AW210528681,DHL,1678089280###AW210528684,DHL,5616504751###AW210529690,China Post,LY797580358CN###AW210529692,DHL,7522327805###AW210530699,DHL,7522330686###AW210530839,DHL,7520934153###AW210530700,DHL,4076065416###AW210531702,DHL,7520935575###AW210531701,China Post,LY798412974CN###AW210531704,DHL,2890730975###AW210531703,China Post,LY796223774CN###AW210601846,DHL,4175340153###AW210601847,DHL,4849407754###AW210602711,China Post,LY796620740CN###AW210602849,DHL,2883175540###AW210602713,DHL,9237338460###AW210602852,DHL,2883109611###AW210602851,DHL,6959584306###AW210602718,DHL,1930030550###AW210603723,DHL,8643384120###AW210603725,DHL,6900577025###AW210604731,DHL,4849371015###AW210604730,DHL,2883143292###AW210605732,DHL,2883453226###AW210514762,DHL,1879149554###AW210605733,USPS,92748927005447000013572725###AW210605854,China Post,LY799965855CN###AW210605857,DHL,1181357155###AW210605858,DHL,7152988135###AW210605735,DHL,2883453005###AW210606859,DHL,6699171161###AW210606737,DHL,6900575883###AW210607742,DHL,1181299626###AW210607744,DHL,1204029470###AW210607748,DHL,8247861650###AW210607750,DHL,4346565904###AW210608865,DHL,5158609750###AW210608752,DHL,6382335120###AW210608866,DHL,1204031253###AW210608756,USPS,92748927005447000013533894###AW210609876,DHL,6208937593###AW210610765,DHL,3798002283###AW210610777,USPS,92748927005447000013542896###AW210611882,DHL,3533312263###AW210611883,DHL,5809076103###AW210611784,DHL,1068191655###AW210611782,DHL,9643706903###AW210611886,DHL,5115517772###AW210611887,DHL,5115516943###AW210611888,DHL,1645120094###AW210612889,DHL,5115486751###AW210613791,DHL,7920077454###AW210614794,USPS,92748927005447000013650201###AW210614898,USPS,92748927005447000013635390###AW210614798,DHL,1645119980###AW210614799,DHL,1645119781###AW210615901,USPS,92748927005447000013564447###AW210615803,USPS,92748927005447000013509820###AW210616906,DHL,4809088690###AW210616809,DHL,5954240865###AW210617918,DHL,5809141284###AW210617820,DHL,5772626366';
        $str2 = '1139,AW210611887###1138,AW210611886###90,201208135###107,201208152###108,201208153###109,201209154###116,AW201211162###117,AW201212164###118,AW201212166###120,AW201214168###123,AW201215171###133,AW201221190###143,AW201225210###149,AW201230220###150,AW201230221###152,AW210101223###154,AW210104225###157,AW210106229###160,AW210108231###163,AW210109236###165,AW210109238###168,AW210110241###170,AW210112243###171,AW210113249###174,AW210114253###178,AW210118260###179,AW210118261###182,AW210119267###198,AW210125309###204,AW210127322###209,AW210128328###210,AW210128329###212,AW210128334###214,AW210129338###217,AW210129341###220,AW210131346###222,AW210131348###227,AW210131354###238,AW210201366###244,AW210203374###246,AW210204378###250,AW210206387###252,AW210206390###256,AW210207394###262,AW210210405###263,AW210210406###268,AW210212411###270,AW210213416###271,AW210214417###272,AW210214418###275,AW210214421###276,AW210214423###280,AW210216427###285,AW210216432###286,AW210217433###288,AW210217435###289,AW210219438###290,AW210219439###291,AW210219440###293,AW210219442###294,AW210220443###295,AW210220444###296,AW210220445###297,AW210220446###299,AW210221448###304,AW210222454###305,AW210222455###306,AW210222457###308,AW210223460###312,AW210224464###316,AW210224469###319,AW210226473###322,AW210227476###324,AW210227478###325,AW210227479###328,AW210227482###329,AW210227484###336,AW210301491###337,AW210301492###338,AW210302495###340,AW210302497###343,AW210302501###344,AW210302502###346,AW210302504###348,AW210303506###351,AW210303509###352,AW210304510###357,AW210305517###358,AW210305518###360,AW210305519###365,AW210307007###368,AW210307009###370,AW210308011###371,AW210308523###375,AW210309013###374,AW210309525###376,AW210309526###377,AW210310016###378,AW210310527###379,AW210310528###390,AW210311024###388,AW210311535###389,AW210311536###393,AW210312026###394,AW210312027###395,AW210312537###396,AW210313028###404,AW210314035###405,AW210314036###407,AW210314038###403,AW210314540###410,AW210315040###417,AW210316546###419,AW210317049###431,AW210319064###428,AW210319549###433,AW210320066###435,AW210320069###438,AW210321072###441,AW210321076###443,AW210321077###445,AW210322079###449,AW210322081###451,AW210322082###453,AW210322084###446,AW210322556###447,AW210322557###450,AW210322558###455,AW210323085###457,AW210323087###459,AW210323561###463,AW210324092###462,AW210324562###470,AW210325096###472,AW210325097###473,AW210325098###465,AW210325565###469,AW210325570###471,AW210325571###482,AW210326103###487,AW210327108###484,AW210327581###485,AW210327582###491,AW210327583###492,AW210327584###494,AW210328112###495,AW210328113###496,AW210328117###493,AW210328585###498,AW210328587###499,AW210329120###501,AW210329591###502,AW210329592###507,AW210330597###508,AW210330598###513,AW210401128###514,AW210401131###515,AW210402603###519,AW210403608###520,AW210403609###523,AW210404613###527,AW210405142###526,AW210405615###528,AW210405616###529,AW210405617###531,AW210406619###532,AW210406620###535,AW210406622###536,AW210406623###538,AW210407150###539,AW210407151###544,AW210407157###546,AW210407625###550,AW210408162###553,AW210408165###555,AW210408167###556,AW210409168###557,AW210409631###563,AW210411638###564,AW210411639###572,AW210412187###579,AW210412195###578,AW210412641###582,AW210413199###583,AW210413200###587,AW210413203###586,AW210413643###597,AW210416217###595,AW210416652###603,AW210417226###604,AW210417227###606,AW210417228###611,AW210418234###621,AW210419244###618,AW210419661###622,AW210419663###625,AW210420247###626,AW210420248###636,AW210421259###634,AW210421668###639,AW210422262###643,AW210422270###650,AW210423276###660,AW210424287###663,AW210424292###655,AW210424675###664,AW210425294###666,AW210425296###668,AW210425299###669,AW210425301###670,AW210425302###671,AW210425303###672,AW210425678###673,AW210425679###678,AW210426308###680,AW210426309###693,AW210428325###697,AW210428327###700,AW210428331###696,AW210428687###702,AW210429335###705,AW210429339###706,AW210429340###712,AW210429346###714,AW210429348###703,AW210429690###720,AW210430355###721,AW210430356###724,AW210430358###716,AW210430696###729,AW210501360###737,AW210501365###739,AW210501366###740,AW210501367###726,AW210501700###727,AW210501701###728,AW210501702###730,AW210501703###734,AW210501705###745,AW210502371###752,AW210502381###753,AW210502383###754,AW210502384###755,AW210502385###759,AW210503392###760,AW210503394###762,AW210503711###763,AW210503712###773,AW210504406###774,AW210504407###777,AW210504411###778,AW210504414###770,AW210504715###780,AW210505416###782,AW210505417###786,AW210505723###789,AW210506427###791,AW210506429###793,AW210506725###801,AW210507442###802,AW210507443###804,AW210507445###807,AW210507450###811,AW210507455###812,AW210507456###803,AW210507730###814,AW210508458###822,AW210508469###824,AW210508470###830,AW210508474###815,AW210508733###823,AW210508734###837,AW210509480###838,AW210510482###839,AW210510483###840,AW210510484###841,AW210510485###844,AW210510742###847,AW210510746###850,AW210511493###853,AW210511499###854,AW210511751###855,AW210511752###865,AW210512513###859,AW210512753###868,AW210513515###871,AW210513518###872,AW210513521###873,AW210514523###875,AW210514525###876,AW210514526###1060,AW210514762###874,AW210514763###878,AW210514765###886,AW210515534###887,AW210515535###888,AW210515538###884,AW210515767###885,AW210515768###889,AW210515769###891,AW210515770###892,AW210516543###898,AW210516548###897,AW210516774###903,AW210517557###907,AW210517561###905,AW210517776###914,AW210518571###915,AW210518572###916,AW210518576###920,AW210518582###925,AW210518584###911,AW210518779###918,AW210518784###923,AW210518785###924,AW210518786###927,AW210519587###935,AW210519588###926,AW210519787###928,AW210519789###930,AW210519791###931,AW210519792###933,AW210519794###934,AW210519795###941,AW210520598###947,AW210520605###942,AW210520799###946,AW210520801###952,AW210521610###949,AW210521803###955,AW210522614###956,AW210522615###957,AW210522616###960,AW210522619###961,AW210522620###966,AW210523630###963,AW210523810###965,AW210523812###967,AW210524631###968,AW210524632###971,AW210524635###976,AW210524639###977,AW210524640###969,AW210524814###970,AW210524815###987,AW210525653###984,AW210525822###993,AW210526657###996,AW210526662###998,AW210526827###999,AW210526829###1013,AW210527680###1014,AW210527834###1016,AW210528681###1020,AW210528684###1025,AW210529690###1027,AW210529692###1032,AW210530699###1034,AW210530700###1033,AW210530839###1035,AW210531701###1036,AW210531702###1037,AW210531703###1038,AW210531704###1042,AW210601846###1045,AW210601847###1047,AW210602711###1049,AW210602713###1056,AW210602718###1050,AW210602849###1054,AW210602851###1055,AW210602852###1061,AW210603723###1063,AW210603725###1067,AW210604730###1068,AW210604731###1070,AW210605732###1072,AW210605733###1078,AW210605735###1071,AW210605854###1076,AW210605857###1077,AW210605858###1081,AW210606737###1082,AW210606859###1087,AW210607742###1090,AW210607744###1095,AW210607748###1096,AW210607750###1099,AW210608752###1103,AW210608756###1098,AW210608865###1101,AW210608866###1114,AW210609876###1118,AW210610765###1127,AW210610777###1133,AW210611782###1134,AW210611784###1131,AW210611882###1132,AW210611883###1140,AW210611888###1142,AW210612889###1145,AW210613791###1149,AW210614794###1153,AW210614798###1154,AW210614799###1152,AW210614898###1163,AW210615803###1159,AW210615901###1173,AW210616809###1166,AW210616906###1190,AW210617820###1182,AW210617918';
        $str2Array = explode('###', $str2);
        $orders = [];
        foreach ($str2Array as $i) {
            $s = explode(',', $i);
            $orders[$s[1]] = $s[0];
        }
        $carriers = [
            'DHL' => '100001',
            'UPS' => '100002',
            'China Post' => '0301',
            'USPS' => '21051'
        ];
        $str1Array = explode('###', $str1);

        $pages = count($str1Array) / $this->page_size + 1;
        $cur_page = 1;
        $increment_ids = [];
        while ($cur_page < $pages) {

            $items = array_slice($str1Array, ($cur_page - 1) * $this->page_size, $this->page_size);
            var_dump('offset:' . ($cur_page - 1) * $this->page_size);
            var_dump('cnt:' . count($items));
            $order_track_data = $data = [];
            foreach ($items as $item) {
                $temp = explode(',', $item);
                if (isset($orders[$temp[0]]) && isset($carriers[$temp[1]])) {
                    $data[] = ['number' => $temp[2], 'carrier' => $carriers[$temp[1]]];
                    $order_track_data[$temp[2]] = $carriers[$temp[1]];

                    //增加物流信息到sales_order_logistics_history表
//                    $logisticsHistory = $this->logisticsHistoryFactory->create();
//                    $logisticsHistory->setData('order_id', $orders[$temp[0]]);
//                    $logisticsHistory->setData('track_number', $temp[2]);
//                    $logisticsHistory->setData('express', $temp[1]);
//                    $logisticsHistory->save();
                }
            }
            $json_result = $this->curlPostJson($this->deploymentConfig['17track_api_url'] . 'register', $data, $this->headers);

            $result = json_decode($json_result, true);
            if ($result['code'] === 0) {
                $acceptedArray = $result['data']['accepted'];
                //修复运输商编号不准确的运单号
                $this->updateCarrierByTrack($acceptedArray, $order_track_data);
            }
            $cur_page++;
        }
        var_dump(implode(',', $increment_ids));
    }

    private function insertHistory()
    {
        $str1 = '1164,9873515735,DHL,AW210615804###1172,7870083874,DHL,AW210616912###1174,5384044691,DHL,AW210616913###1189,5998504632,DHL,AW210617819###1196,6763167401,DHL,AW210618828###1198,5836930061,DHL,AW210618924###1198,8274556231,5836930061,DHL,AW210618924###1204,3508063705,DHL,AW210619837###1206,92748927005447000013750765,USPS,AW210619838###1207,3308163375,DHL,AW210619839###1209,4525227766,DHL,AW210620933###1213,5998561085,DHL,AW210621846###1214,3643089925,DHL,AW210621937###1215,92748927005447000013726388,USPS,AW210621847###1216,3338156953,DHL,AW210621848###1218,5570547511,DHL,AW210621940###1226,5564246225,DHL,AW210622945###1230,3525464541,DHL,AW210622947###1231,2719242341,DHL,AW210622948###1234,1526879911,DHL,AW210622854###1234,1526879911,DHL,AW210622854###1240,1829123085,DHL,AW210623956###1241,9073391482,DHL,AW210623858###1244,3338133551,DHL,AW210623862###1246,92748927005447000013780533,USPS,AW210624863###1249,4777969641,DHL,AW210624868###1253,9199492794,DHL,AW210626875###1254,4048174443,DHL,AW210626877###1256,6924575210,DHL,AW210626963###1257,3525473184,DHL,AW210626964###1260,92748927005447000013751106,USPS,AW210627880###1262,5836842771,DHL,AW210627883###1263,92748927005447000013768159,USPS,AW210627884###1264,4048363336,DHL,AW210627885###1267,7915794773,DHL,AW210628887###1268,6924613522,DHL,AW210628969###1270,4193635471,DHL,AW210628971###1271,3524822173,DHL,AW210628888###1273,2178277312,DHL,AW210628889###1274,1031141451,DHL,AW210628890###1275,92748927005447000013771371,USPS,AW210628973###1286,92748927005447000013756965,USPS,AW210630902###1298,92748927005447000013791591,USPS,AW210701911###1305,1557724416,DHL,AW210701915';
        $carriers = [
            'DHL' => '100001',
            'UPS' => '100002',
            'China Post' => '0301',
            'USPS' => '21051'
        ];
        $strArray = explode('###', $str1);
        $pages = count($strArray) / $this->page_size + 1;
        $cur_page = 1;
        while ($cur_page < $pages) {
            $items = array_slice($strArray, ($cur_page - 1) * $this->page_size, $this->page_size);
            var_dump('offset:' . ($cur_page - 1) * $this->page_size);
            var_dump('cnt:' . count($items));
            $order_track_data = $data = [];

            foreach ($strArray as $item) {
                $temp = explode(',', $item);
                if (isset($carriers[$temp[2]])) {
                    $data[] = ['number' => $temp[1], 'carrier' => $carriers[$temp[2]]];
                    $order_track_data[$temp[1]] = $carriers[$temp[2]];

                    //增加物流信息到sales_order_logistics_history表
                    $logisticsHistory = $this->logisticsHistoryFactory->create();
                    $logisticsHistory->setData('order_id', $temp[0]);
                    $logisticsHistory->setData('increment_id', $temp[3]);
                    $logisticsHistory->setData('track_number', $temp[1]);
                    $logisticsHistory->setData('express', $temp[2]);
                    $logisticsHistory->save();
                }
            }

            $json_result = $this->curlPostJson($this->deploymentConfig['17track_api_url'] . 'register', $data, $this->headers);

            $result = json_decode($json_result, true);
            if ($result['code'] === 0) {
                $acceptedArray = $result['data']['accepted'];
                //修复运输商编号不准确的运单号
                $this->updateCarrierByTrack($acceptedArray, $order_track_data);
            }
            $cur_page++;
        }
        die;
    }
}
