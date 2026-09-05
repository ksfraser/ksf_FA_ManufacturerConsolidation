<?php
declare(strict_types=1);

define('SS_ksf_FA_ManufacturerConsolidation', 150 << 8);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/ComposerDependencies.php';

class hooks_ksf_FA_ManufacturerConsolidation extends hooks
{
    var $module_name = 'ksf_FA_ManufacturerConsolidation';
    var $version = '2.4.19-1.0.0';

    function install_extension($check_only=true)
    {
        if (!$check_only) {
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
        }
        return true;
    }

    function activate_extension($company, $check_only=true)
    {
        if (!file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            return true;
        }

        $updates = array(
            'install.sql' => array(
                'ksf_supplier_moq_rules',
                'ksf_consolidation_groups',
                'ksf_consolidation_lines',
                'ksf_consolidation_recommendations',
            ),
        );

        return $this->update_databases($company, $updates, $check_only);
    }

    function deactivate_extension($company, $check_only=true)
    {
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

    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->checkMoqGaps();

        $data['consolidation_data'] = $handler->getPendingRecommendations();
        $data['consolidation_processed'] = true;

        hook_invoke_all('consolidation_data', $data);
    }

    function suggested_po_approved(array &$data): void
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->evaluateForConsolidation($data);
    }

    function stock_turnover_data(array &$data): void
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->updateFromTurnoverData($data);
    }

    function po_tracking_data(array &$data): void
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->updateFromPoTrackingData($data);
    }

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

    private function getHandler()
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        if (!class_exists('\Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationHandler')) {
            return null;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationRepository($db);
        $handler = new \Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationHandler($repository);

        return $handler;
    }

    function install_access()
    {
        $security_sections[SS_ksf_FA_ManufacturerConsolidation] = _("Manufacturer Consolidation");
        $security_areas['SA_ksf_FA_MFG_CONS'] = array(
            SS_ksf_FA_ManufacturerConsolidation | 1,
            _("Manage Manufacturer Consolidation")
        );
        $security_areas['SA_ksf_FA_MFG_CONS_VIEW'] = array(
            SS_ksf_FA_ManufacturerConsolidation | 2,
            _("View Manufacturer Consolidation")
        );
        return array($security_areas, $security_sections);
    }
}