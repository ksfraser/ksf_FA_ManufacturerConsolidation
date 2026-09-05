<?php
declare(strict_types=1);

define('SS_ksf_FA_ManufacturerConsolidation', 150 << 8);

class hooks_ksf_FA_ManufacturerConsolidation extends hooks
{
    var $module_name = 'ksf_FA_ManufacturerConsolidation';
    var $version = '2.4.19-1.0.0';

    function install_extension($check_only=true)
    {
        if (!$check_only) {
            $this->_ensureComposerDependencies();
        }
        return true;
    }

    function activate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $autoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        $sqlFile = __DIR__ . '/sql/install.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('0_', get_company_preference($company)['_prefix'], $sql);
            run_db_import($sql, $company);
        }

        add_security_section(SS_ksf_FA_ManufacturerConsolidation, 'Manufacturer Consolidation', 'SA_INVENTORY');
        return true;
    }

    function deactivate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $uninstallFile = __DIR__ . '/sql/uninstall.sql';
        if (file_exists($uninstallFile)) {
            $sql = file_get_contents($uninstallFile);
            run_db_import($sql, $company);
        }

        remove_security_section(SS_ksf_FA_ManufacturerConsolidation);
        return true;
    }

    function getModuleConstants(&$data, $opts = [])
    {
        $data['constants']['SS_ksf_FA_ManufacturerConsolidation'] = SS_ksf_FA_ManufacturerConsolidation;
        $data['constants']['SA_ksf_FA_MFG_CONS'] = SS_ksf_FA_ManufacturerConsolidation | 1;
        $data['constants']['SA_ksf_FA_MFG_CONS_VIEW'] = SS_ksf_FA_ManufacturerConsolidation | 2;
        return $data;
    }

    function getModuleCapabilities(&$data, $opts = [])
    {
        $data['capabilities']['manufacturer_consolidation'] = [
            'view' => 'SA_ksf_FA_MFG_CONS_VIEW',
            'manage' => 'SA_ksf_FA_MFG_CONS',
        ];
        return $data;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $capability = isset($opts['capability']) ? $opts['capability'] : (isset($data['capability']) ? $data['capability'] : null);
        if ($capability === null) {
            $data['has_capability'] = false;
            return false;
        }
        $caps = ['view', 'manage'];
        $hasCapability = in_array($capability, $caps);
        $data['has_capability'] = $hasCapability;
        return $hasCapability;
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = isset($opts['request']) ? $opts['request'] : (isset($data['request']) ? $data['request'] : 'capabilities');
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'has:') === 0) {
            $capability = substr($request, 4);
            return $this->hasCapability($data, ['capability' => $capability]);
        }

        switch ($request) {
            case 'capabilities':
                $data['capabilities'] = $this->getModuleCapabilities($data, $opts);
                return $data['capabilities'];
            default:
                return null;
        }
    }

    /**
     * Cron hook: nightly_recalc - check for MOQ gaps and generate recommendations.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        $handler->checkMoqGaps();

        $data['consolidation_data'] = $handler->getPendingRecommendations();
        $data['consolidation_processed'] = true;

        hook_invoke_all('consolidation_data', $data);
    }

    /**
     * Listen for suggested_po_approved hook.
     *
     * @param array &$data {
     *     @var int $suggested_order_id
     *     @var int $supplier_id
     *     @var array $lines
     * }
     *
     * @since 1.0.0
     */
    function suggested_po_approved(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->evaluateForConsolidation($data);
    }

    /**
     * Listen for stock_turnover_data from StockTurnover module.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function stock_turnover_data(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->updateFromTurnoverData($data);
    }

    /**
     * Listen for po_tracking_data from PurchaseOrderTracking module.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function po_tracking_data(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->updateFromPoTrackingData($data);
    }

    /**
     * Broadcast consolidation_suggested hook.
     *
     * @param array $recommendations
     *
     * @since 1.0.0
     */
    public function broadcastConsolidationSuggested(array $recommendations): void
    {
        $data = [
            'module' => 'ksf_FA_ManufacturerConsolidation',
            'event' => 'consolidation_suggested',
            'recommendations' => $recommendations,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        hook_invoke_all('consolidation_suggested', $data);
    }

    private function getHandler(): \Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationHandler
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationRepository($db);
        $handler = new \Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationHandler($repository);

        return $handler;
    }

    private function _ensureComposerDependencies(): void
    {
        $composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
        if (file_exists($composerDepsPath)) {
            require_once $composerDepsPath;
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
        }
    }

    function hook_invoke_all($hook, &$data)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return null;
        }
        require_once $autoload;

        return parent::hook_invoke_all($hook, $data);
    }
}