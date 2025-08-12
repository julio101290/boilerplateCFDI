<?php

namespace julio101290\boilerplateCFDI\Models;

use CodeIgniter\Model;

class XmlModel extends Model {

    protected $table = 'xml';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'id'
        , 'uuidTimbre'
        , 'archivoXML'
        , 'serie'
        , 'folio'
        , 'rfcEmisor'
        , 'rfcReceptor'
        , 'nombreEmisor'
        , 'nombreReceptor'
        , 'tipoComprobante'
        , 'fecha'
        , 'fechaTimbrado'
        , 'total'
        , 'created_at'
        , 'deleted_at'
        , 'updated_at'
        , 'idEmpresa'
        , 'metodoPago'
        , 'formaPago'
        , 'usoCFDI'
        , 'exportacion'
        , 'idEmpresa'
        , 'base16'
        , 'totalImpuestos16'
        , 'base8'
        , 'totalImpuestos8'
        , 'emitidoRecibido'
        , 'status'
        , 'tasaExenta'
        , 'uuidPaquete'
        , 'motivoCancelacion'
        , 'uuidRelacionado'
        , 'observacionesCancelacion'
        , 'acuseCancelacion'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function getIngresosXMLGrafica($empresas, $RFCEmpresas, $desdeFecha, $hastaFecha) {
        // Detectar si estamos usando PostgreSQL o MySQL/MariaDB
        $dbDriver = $this->db->DBDriver;

        // Función para formatear nombre de columna
        if ($dbDriver === 'Postgre') {
            $col = function ($name) {
                return "\"xml\".\"{$name}\"";
            };
            $periodExpr = "TO_CHAR(\"xml\".\"fecha\", 'YYYYMM')";
        } else {
            $col = function ($name) {
                return "`xml`.`{$name}`";
            };
            $periodExpr = "DATE_FORMAT(`xml`.`fecha`, '%Y%m')";
        }

        // Escapar manualmente los RFCs para armar el IN (...) de forma segura
        $escapedRFCs = "('" . implode("','", array_map([$this->db, 'escapeString'], $RFCEmpresas)) . "')";

        $builder = $this->db->table('xml');

        // Ingresos
        $ingresosCase = "
        SUM(CASE 
            WHEN 
                ({$col('rfcEmisor')} IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'I') OR
                ({$col('rfcReceptor')} IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'N') OR
                ({$col('rfcReceptor')} IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'E')
            THEN {$col('total')}
            ELSE 0
        END) as ingreso
    ";

        // Egresos
        $egresosCase = "
        SUM(CASE 
            WHEN 
                ({$col('rfcEmisor')} IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'E') OR
                ({$col('rfcReceptor')} NOT IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'N') OR
                ({$col('rfcReceptor')} IN {$escapedRFCs} AND {$col('tipoComprobante')} = 'I')
            THEN {$col('total')}
            ELSE 0
        END) as egreso
    ";

        $builder->select("{$periodExpr} as periodo", false)
                ->select($ingresosCase, false)
                ->select($egresosCase, false)
                ->whereIn('xml.idEmpresa', $empresas)
                ->where("{$col('fechaTimbrado')} >=", $desdeFecha . ' 00:00:00')
                ->where("{$col('fechaTimbrado')} <=", $hastaFecha . ' 23:59:59')
                ->groupBy($periodExpr);

        return $builder->get();
    }

    public function getEgresosXMLGrafica($empresas, $RFCEmpresas, $desdeFecha, $hastaFecha) {


        $result = $this->db->table('xml')
                ->select('date_format(fecha,\'%Y%m\') as periodo,sum(total) as total')
                ->whereIn('tipoComprobante', array('i'))
                ->whereIn('rfcReceptor', $RFCEmpresas)
                ->groupStart()
                ->where('tipoComprobante', 'i')
                ->orWhereIn('idEmpresa', $empresas)
                ->groupend()
                ->where('fechaTimbrado >=', $desdeFecha . ' 00:00:00')
                ->where('fechaTimbrado <=', $hastaFecha . ' 23:59:59')
                ->groupBy('date_format(fecha,\'%Y%m\')')
                ->get();

        return $result;
    }

    public function mdlGetRFCReceptor($idEmpresa, $searchTerm) {


        $resultado = $this->db->table('xml')
                        ->select('rfcReceptor')
                        ->where('idEmpresa', $idEmpresa)
                        ->like("rfcReceptor", $searchTerm)
                        ->groupBy('rfcReceptor')
                        ->limit(100)
                        ->get()->getResultArray();

        return $resultado;
    }

    public function mdlGetRFCEmisor($idEmpresa, $searchTerm) {


        $resultado = $this->db->table('xml')
                        ->select('rfcEmisor')
                        ->where('idEmpresa', $idEmpresa)
                        ->like("rfcEmisor", $searchTerm)
                        ->groupBy('rfcEmisor')
                        ->limit(100)
                        ->get()->getResultArray();

        return $resultado;
    }

    public function mdlGetXMLFilter() {

        $resultado = $this->db->table('xml')
                ->groupStart()
                ->where('rfcReceptor', 'CGU840103SZ5')
                ->orWhere("'0'='CGU840103SZ5' ")
                ->groupEnd();

        return $resultado;
    }

    public function mdlXMLSinAsignar($empresas, $tipoComprobante, $params = []) {
        $dbDriver = $this->db->getPlatform();

        // Condición SQL raw para subconsulta, ajustada por motor
        $subquery = $dbDriver === 'Postgre' ? 'NOT EXISTS (SELECT 1 FROM enlacexml b WHERE "a"."uuidTimbre" = "b"."uuidXML")' : 'NOT EXISTS (SELECT 1 FROM enlacexml b WHERE a.uuidTimbre = b.uuidXML)';

        $builder = $this->db->table('xml a')
                ->select('a.id, a.uuidTimbre, serie, folio, rfcReceptor, rfcEmisor, nombreReceptor, total, fecha, tipoComprobante')
                ->where($subquery, null, false)
                ->where('a.tipoComprobante', $tipoComprobante)
                ->whereIn('a.idEmpresa', $empresas);

        // 🔍 Filtros por columna específicos
        if (!empty($params['columns'])) {
            foreach ($params['columns'] as $col) {
                if (!empty($col['search']['value'])) {
                    $builder->like($col['data'], $col['search']['value']);
                }
            }
        }

        // 🔎 Búsqueda global en campos específicos
        $search = $params['search']['value'] ?? '';
        if (!empty($search)) {
            $builder->groupStart();
            $camposBusqueda = ['uuidTimbre', 'serie', 'folio', 'rfcReceptor', 'nombreReceptor'];
            foreach ($camposBusqueda as $campo) {
                $builder->orLike("a.$campo", $search);
            }
            $builder->groupEnd();
        }

        // ↕ Ordenamiento
        if (!empty($params['order'])) {
            foreach ($params['order'] as $ord) {
                $colIndex = $ord['column'];
                $dir = $ord['dir'] ?? 'asc';
                $colName = $params['columns'][$colIndex]['data'];
                $builder->orderBy($colName, $dir);
            }
        }

        // 📄 Paginación
        if (isset($params['length']) && $params['length'] != -1) {
            $builder->limit($params['length'], $params['start']);
        }

        $data = $builder->get()->getResultArray();

        // 🔢 Total sin filtros
        $totalBuilder = $this->db->table('xml a')
                ->where($subquery, null, false)
                ->where('a.tipoComprobante', $tipoComprobante)
                ->whereIn('a.idEmpresa', $empresas);

        $total = $totalBuilder->countAllResults();

        return [
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
        ];
    }

    /**
     * Buscar parcialidad
     */
    public function mdlParcialidadVenta($idVenta, $idPago) {
        return $this->db->table('payments a')
                        ->join('sells b', 'a.idSell = b.id')
                        ->join('pagos d', 'a.idComplemento = d.id')
                        ->join('enlacexml c', 'd.id = c.idDocumento')
                        ->join('xml e', 'e.uuidTimbre = c.uuidXML')
                        ->where('e.status', 'vigente')
                        ->where('b.id', $idVenta)
                        ->where('d.id !=', $idPago)
                        ->countAllResults();
    }

    /**
     * Buscar parcialidad
     */
    public function mdlSaldo($idVenta, $idPago) {
        // Obtener importe a pagar
        $importeAPagar = $this->db->table('payments a')
                ->join('sells b', 'a.idSell = b.id')
                ->join('pagos d', 'a.idComplemento = d.id')
                ->join('enlacexml c', 'd.id = c.idDocumento')
                ->join('xml e', 'e.uuidTimbre = c.uuidXML')
                ->selectSum('a.importPayment')
                ->where('e.status', 'vigente')
                ->where('b.id', $idVenta)
                ->where('d.id !=', $idPago)
                ->get()
                ->getRowArray();

        // Obtener importe devuelto
        $importeDevuelto = $this->db->table('payments a')
                ->join('sells b', 'a.idSell = b.id')
                ->join('pagos d', 'a.idComplemento = d.id')
                ->join('enlacexml c', 'd.id = c.idDocumento')
                ->join('xml e', 'e.uuidTimbre = c.uuidXML')
                ->selectSum('a.importBack')
                ->where('e.status', 'vigente')
                ->where('b.id', $idVenta)
                ->where('d.id !=', $idPago)
                ->get()
                ->getRowArray();

        // Normalizar valores nulos o faltantes
        $importeAPagarVal = isset($importeAPagar['importPayment']) ? $importeAPagar['importPayment'] : 0;
        $importeDevueltoVal = isset($importeDevuelto['importBack']) ? $importeDevuelto['importBack'] : 0;

        return $importeAPagarVal - $importeDevueltoVal;
    }

    public function obtenerXMLDatatable($params) {
        $columns = [
            'id',
            'uuidTimbre',
            'archivoXML',
            'rfcEmisor',
            'rfcReceptor',
            'nombreEmisor',
            'nombreReceptor',
            'serie',
            'folio',
            'tipoComprobante',
            'fecha',
            'fechaTimbrado',
            'total',
            'metodoPago',
            'formaPago',
            'usoCFDI',
            'exportacion',
            'created_at',
            'updated_at',
            'deleted_at',
            'status',
            'uuidPaquete'
        ];

        $builder = $this->db->table('xml')
                ->select($columns)
                ->where('deleted_at', null);

        // Filtro por empresa
        if (!empty($params['idEmpresa'])) {
            $builder->whereIn('idEmpresa', $params['idEmpresa']);
        }

        // Búsqueda global
        if (!empty($params['search']['value'])) {
            $search = $params['search']['value'];
            $builder->groupStart();
            foreach ($columns as $col) {
                $builder->orLike($col, $search);
            }
            $builder->groupEnd();
        }

        // Ordenamiento
        if (isset($params['order'][0])) {
            $orderColumn = $columns[$params['order'][0]['column']] ?? 'id';
            $orderDir = $params['order'][0]['dir'] ?? 'DESC';
            $builder->orderBy($orderColumn, $orderDir);
        } else {
            $builder->orderBy('id', 'DESC');
        }

        // Clonar para contar filtrados
        $builderCount = clone $builder;
        $recordsFiltered = $builderCount->countAllResults(false);

        // Paginación
        if (isset($params['start']) && isset($params['length']) && $params['length'] != -1) {
            $builder->limit((int) $params['length'], (int) $params['start']);
        }

        $data = $builder->get()->getResultArray();
        $recordsTotal = $this->db->table('xml')->where('deleted_at', null)->countAllResults();

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function mdlGetXmlFilters(
            array $empresasID,
            string $desdeFecha,
            string $hastaFecha,
            string $todas,
            string $RFCEmisor = '0',
            string $RFCReceptor = '0',
            string $metodoPago = '0',
            string $formaPago = '0',
            string $usoCFDI = '0',
            string $tipoComprobante = '0',
            string $emitidoRecibido = '0',
            string $status = '0',
            array $params = []
    ): array {
        $builder = $this->db->table('xml') // Reemplaza con tu tabla real
                ->select('*')
                ->where('deleted_at', null)
                ->whereIn('idEmpresa', $empresasID)
                ->where('fechaTimbrado >=', $desdeFecha . ' 00:00:00')
                ->where('fechaTimbrado <=', $hastaFecha . ' 23:59:59');

        if ($todas !== 'true') {
            $builder->whereIn('rfcEmisor', $empresasID); // Ajustar si usas RFCs diferentes
        }

        if ($RFCEmisor !== '0') {
            $builder->where('rfcEmisor', $RFCEmisor);
        }

        if ($RFCReceptor !== '0') {
            $builder->where('rfcReceptor', $RFCReceptor);
        }

        if ($metodoPago !== '0') {
            $builder->where('metodoPago', $metodoPago);
        }

        if ($formaPago !== '0') {
            $builder->where('formaPago', $formaPago);
        }

        if ($usoCFDI !== '0') {
            $builder->where('usoCFDI', $usoCFDI);
        }

        if ($tipoComprobante !== '0') {
            $builder->where('tipoComprobante', $tipoComprobante);
        }

        if ($emitidoRecibido !== '0') {
            $builder->where('emitidoRecibido', $emitidoRecibido);
        }

        if ($status !== '0') {
            $builder->where('status', $status);
        }

        // 🔍 Búsqueda global
        $search = $params['search']['value'] ?? '';
        if (!empty($search)) {
            $builder->groupStart();
            foreach ($params['columns'] as $col) {
                if (!empty($col['data']) && is_string($col['data'])) {
                    $builder->orLike($col['data'], $search);
                }
            }
            $builder->groupEnd();
        }

        // 🔎 Filtros por columna individuales
        foreach ($params['columns'] ?? [] as $col) {
            if (!empty($col['search']['value']) && !empty($col['data'])) {
                $builder->like($col['data'], $col['search']['value']);
            }
        }

        // 🔃 Ordenamiento
        if (!empty($params['order'])) {
            foreach ($params['order'] as $ord) {
                $idx = $ord['column'];
                $dir = $ord['dir'] ?? 'asc';
                $col = $params['columns'][$idx]['data'] ?? null;
                if ($col) {
                    $builder->orderBy($col, $dir);
                }
            }
        }

        // 🔢 Paginación
        if (!empty($params['length']) && $params['length'] != -1) {
            $builder->limit($params['length'], $params['start']);
        }

        $data = $builder->get()->getResultArray();

        // Conteo total sin filtros
        $totalBuilder = $this->db->table('xml')
                ->where('deleted_at', null)
                ->whereIn('idEmpresa', $empresasID)
                ->where('fechaTimbrado >=', $desdeFecha . ' 00:00:00')
                ->where('fechaTimbrado <=', $hastaFecha . ' 23:59:59');

        if ($todas !== 'true') {
            $totalBuilder->whereIn('rfcEmisor', $empresasID);
        }

        if ($RFCEmisor !== '0') {
            $totalBuilder->where('rfcEmisor', $RFCEmisor);
        }

        if ($RFCReceptor !== '0') {
            $totalBuilder->where('rfcReceptor', $RFCReceptor);
        }

        if ($metodoPago !== '0') {
            $totalBuilder->where('metodoPago', $metodoPago);
        }

        if ($formaPago !== '0') {
            $totalBuilder->where('formaPago', $formaPago);
        }

        if ($usoCFDI !== '0') {
            $totalBuilder->where('usoCFDI', $usoCFDI);
        }

        if ($tipoComprobante !== '0') {
            $totalBuilder->where('tipoComprobante', $tipoComprobante);
        }

        if ($emitidoRecibido !== '0') {
            $totalBuilder->where('emitidoRecibido', $emitidoRecibido);
        }

        if ($status !== '0') {
            $totalBuilder->where('status', $status);
        }

        $total = $totalBuilder->countAllResults();

        return [
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
        ];
    }
}
