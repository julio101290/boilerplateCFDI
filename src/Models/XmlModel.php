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
        if (count($RFCEmpresas) > 1) {

            $coma = "";
        } else {

            $coma = "'";
        }
        $rfcImplode = implode("',", $RFCEmpresas);

        $result = $this->db->table('xml')
                ->select('date_format(fecha,\'%Y%m\') as periodo
            ,sum(case when
            (rfcEmisor in(\'' . $rfcImplode . $coma . ') and tipoComprobante=\'I\') or ( rfcReceptor in(\'' . $rfcImplode . $coma . ') and tipoComprobante=\'N\')  
             or ( rfcReceptor in (\'' . $rfcImplode . $coma . ') and tipoComprobante=\'E\')
             then total
            else 0
            end) as ingreso,sum(case when
            (rfcEmisor in (\'' . $rfcImplode . $coma . ') and tipoComprobante=\'E\') or ( rfcReceptor not in(\'' . $rfcImplode . $coma . ') and tipoComprobante=\'N\')  
             or ( rfcReceptor in (\'' . $rfcImplode . $coma . ') and tipoComprobante=\'I\')
             then total
            else 0
            end) as egreso')
                ->whereIn('idEmpresa', $empresas)
                ->where('fechaTimbrado >=', $desdeFecha . ' 00:00:00')
                ->where('fechaTimbrado <=', $hastaFecha . ' 23:59:59')
                ->groupBy('date_format(fecha,\'%Y%m\')')
                ->get();
        return $result;
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
}
