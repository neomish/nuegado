<?php

    # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
    # Librería para gestión de plantillas con PHP                                 #
    # Autor: José David Calderón Serrano                                          #
    #        jose.david@calderonbonilla.org, neomish@gmail.com, gato@kalmish.com  #
    #                                                                             #
    # Este programa/libreria es software libre: usted puede redistribuirlo y/o    #
    # modificarlo bajo los términos de la GNU General Public License publicada    #
    # por la Free Software Foundation, bien de la versión 3 de la Licencia, o     #
    # (a su elección) cualquier versión posterior.                                #
    #                                                                             #
    # Este programa se distribuye con la esperanza de que sea útil, pero SIN      #
    # NINGUNA GARANTÍA, incluso sin la garantía implícita de COMERCIALIZACIÓN o   #
    # IDONEIDAD PARA UN PROPÓSITO PARTICULAR. Consulte la GNU General Public      #
    # License para más detalles.                                                  #
    #                                                                             #
    # Debería haber recibido una copia de la Licencia Pública General GNU junto   #
    # con este programa, vease el archivo licencia-gpl.v3.es.txt, Si no, vea      #
    # https://www.gnu.org/licenses/gpl.html                                       #
    # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

    /**
     *  Clases
     *
     *  Conjunto de posibles clases para facilitar el desarrollo de aplicaciones
     **/

    /*
        Clase para obtener listado del contenido de los directorios
    */

    class Directorio {
        private $ruta = "";
        public $contenido;

        public function __construct($ruta = "./", $rutaReal = false) {
            $this->ruta =
                $rutaReal === true
                    ? $this->rutaReal($ruta)
                    : $this->rutaCortada($ruta);
        }

        private function rutaReal($ruta = "./") {
            file_exists($ruta)
                ? ($ruta = realpath($ruta))
                : ($ruta = realpath("./"));
            return is_dir($ruta)
                ? ($ruta == "/"
                    ? $ruta
                    : $ruta . "/")
                : pathinfo($ruta, PATHINFO_DIRNAME) . "/";
        }

        private function rutaCortada($ruta = "./") {
            return strpos(realpath($ruta), getcwd()) === 0
                ? str_replace(
                    getcwd(),
                    "./",
                    substr($ruta, -1) == "/" ? $ruta : $ruta . "/"
                )
                : "./";
        }

        public function esculcar($tipos = [], $carpetas = true, $ficheros = true) {
            $archivos = [];
            $listado = @scandir($this->ruta);
            if (is_array($listado)) {
                foreach ($listado as $elemento) {
                    $ruta = $this->ruta . $elemento;
                    $extension = substr(strrchr($elemento, "."), 1);
                    $verCarpeta = is_dir($ruta) && $carpetas;
                    $verFichero = !is_dir($ruta) && $ficheros;
                    $cumplePatron = $this->cumplePatrones($elemento, $tipos);
                    if (
                        !in_array($elemento, [".", ".."]) &&
                        ($verCarpeta || $verFichero) &&
                        $cumplePatron
                    ) {
                        $archivos[] = [
                            "RUTA" => $ruta,
                            "NOMBRE" => $elemento,
                            "PERMISOS" => $this->verPermisos($ruta),
                            "MODOS" => sprintf("%o", fileperms($ruta)),
                            "PESO" => $this->verPeso($ruta),
                            "PESO_REAL" => $this->verPeso($ruta, false),
                            "EXTENSION" => $extension,
                            "MIME" => $this->verMime($ruta),
                        ];
                    }
                }
            }
            $this->contenido = $archivos;
        }

        public function listar($tipos = [], $carpetas = true, $ficheros = true) {
            $this->esculcar($tipos, $carpetas, false);
            $listado = $this->contenido;
            $this->esculcar($tipos, false, $ficheros);
            if (!empty($listado)) {
                $this->contenido = array_merge($listado, $this->contenido);
            }
        }

        function listarRecursivamente($excluir = []) {
            $recavar = new Directorio($this->ruta);
            $recavar->listar();
            $lista = $recavar->contenido;
            if (!empty($recavar->contenido)) {
                foreach ($recavar->contenido as $subdirectorio) {
                    if (
                        !is_link($subdirectorio["RUTA"]) &&
                        is_dir($subdirectorio["RUTA"])
                    ) {
                        $subcarpeta = new Directorio($subdirectorio["RUTA"] . "/");
                        $subcarpeta->listarRecursivamente();
                        $lista = array_merge($lista, $subcarpeta->contenido);
                    }
                }
            }
            $this->contenido = $lista;
        }

        private function cumplePatrones( $texto, $vector ) {
            $devolver = false;
            if (empty($vector)) {
                $devolver = true;
            } else {
                foreach ($vector as $patron) {
                    if (strpos($texto, $patron) !== false) {
                        $devolver = true;
                    }
                }
            }
            return $devolver;
        }

        private function verPermisos($ruta) {
            $permisos = @fileperms($ruta);
            switch ($permisos & 0xf000) {
                case 0xc000: // Socket
                    $info = "s";
                    break;
                case 0xa000: // Enlace simbólico
                    $info = "l";
                    break;
                case 0x8000: // Normal
                    $info = "r";
                    break;
                case 0x6000: // Bloque especial
                    $info = "b";
                    break;
                case 0x4000: // Directorio
                    $info = "d";
                    break;
                case 0x2000: // Carácter especial
                    $info = "c";
                    break;
                case 0x1000: // Tubería FIFO pipe
                    $info = "p";
                    break;
                default:
                    // Desconocido
                    $info = "u";
                    break;
            }
            // Propietario
            $info .= $permisos & 0x0100 ? "r" : "-";
            $info .= $permisos & 0x0080 ? "w" : "-";
            $info .=
                $permisos & 0x0040
                    ? ($permisos & 0x0800
                        ? "s"
                        : "x")
                    : ($permisos & 0x0800
                        ? "S"
                        : "-");
            // Grupo
            $info .= $permisos & 0x0020 ? "r" : "-";
            $info .= $permisos & 0x0010 ? "w" : "-";
            $info .=
                $permisos & 0x0008
                    ? ($permisos & 0x0400
                        ? "s"
                        : "x")
                    : ($permisos & 0x0400
                        ? "S"
                        : "-");
            // Mundo
            $info .= $permisos & 0x0004 ? "r" : "-";
            $info .= $permisos & 0x0002 ? "w" : "-";
            $info .=
                $permisos & 0x0001
                    ? ($permisos & 0x0200
                        ? "t"
                        : "x")
                    : ($permisos & 0x0200
                        ? "T"
                        : "-");
            return $info;
        }

        private function verPeso($ruta, $legible = true) {
            $devolver = "";
            $peso = @filesize($ruta);
            if (is_numeric($peso)) {
                if ($legible) {
                    $divisor = 1024;
                    $paso = 0;
                    $prefijo = ["B", "KiB", "MiB", "GiB", "TiB", "PiB"];
                    while ($peso / $divisor > 0.9) {
                        $peso = $peso / $divisor;
                        $paso++;
                    }
                    $devolver = round($peso, 2) . " " . $prefijo[$paso];
                } else {
                    $devolver = $peso;
                }
            } else {
                $devolver = "NaN";
            }
            return $devolver;
        }

        private function verMime($ruta) {
            $mime = @mime_content_type($ruta);
            return $mime ? trim($mime) : "No se pudo identificar";
        }
    }

    /*
        Clase para acceder a un recurso privado o público mediante php
    */
    class Recurso {
        private $ruta;
        private $accesible;

        function __construct($ruta, $accesible = true) {
            $this->ruta =
                strpos(realpath($ruta), getcwd()) === 0
                    ? str_replace(getcwd(), "./", $ruta)
                    : "";
            $this->accesible =
                file_exists($this->ruta) && is_file($this->ruta) && $accesible;
        }

        private function volcarArchivo($empotrado = true) {
            $nombre = basename($this->ruta);
            $tipoMime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->ruta);
            $empotrable = $empotrado === true ? "inline" : "attachment";
            header("Content-Disposition: $empotrable; filename=$nombre;");
            header("Content-Type: $tipoMime");
            header("Content-Length: " . filesize($this->ruta));
            $gestor = fopen($this->ruta, "rb");
            fpassthru($gestor);
            exit();
        }

        private function volcarProhibido($empotrado = true) {
            $empotrable = $empotrado === true ? "inline" : "attachment";
            header("Content-Disposition: $empotrable; filename=nodisponible.png;");
            header("Content-Type: image/png");
            header("Content-Length: 288");
            $equis =
                "iVBORw0KGgoAAAANSUhEUgAAABUAAAAYCAYAAAAVibZIAAAA" .
                "CXBIWXMAAAxOAAAMTgF/d4wjAAAA0klEQVQ4ja1VWw6EMAic" .
                "bvZG3Kl30jtxJvbDba0pL6skJCaF6cAgLXjBhEgAAMxArfj2" .
                "g1qlfZd9L2kw5gNMOxTgdCKRbZM58iRwiR/ybNDmA/sQEJB0" .
                "4AhsXm4QgBn8T3ABx7Ldvt5xp/8+27ssU73NiPMKW02cR2yd" .
                "sj+pmzQjyse+pnoHXBgrH3BB+XCkHg2/+ouusjTacKiv7cPR" .
                "iEK1J7bZnkUV5UpXlnVqn7oCGXNo5filB8+JxbidlWmAiVCY" .
                "Uw9fA+9g7cHsNxLlt05gPywfP/t9rdo4AAAAAElFTkSuQmCC";
            $imagen = imagecreatefromstring(base64_decode($equis));
            imagecolortransparent($imagen, imagecolorallocate($imagen, 0, 0, 0));
            imagepng($imagen);
            imagedestroy($imagen);
        }

        function volcar($empotrado = true) {
            if ($this->accesible) {
                $this->volcarArchivo($empotrado);
            } else {
                $this->volcarProhibido($empotrado);
            }
        }
    }

    /*
        Clase que permite el manejo de archivos html de plantillas tpl
    */
    class Plantilla {
        private $archivo;
        private $plantilla;
        private $programa;
        private $fd;
        private $datos;
        private $pila;
        public $html;

        function __construct($archivo = "", $valores = []) {
            $this->datos = $valores;
            $this->archivo = file_exists($archivo) ? $archivo : false;
            $this->programa = $this->archivo
                ? substr($this->archivo, 0, -3) . "php"
                : false;
            if ($this->archivo && ($this->fd = @fopen($this->archivo, "r"))) {
                $this->plantilla =
                    filesize($this->archivo) > 0
                        ? @fread($this->fd, filesize($this->archivo))
                        : "";
                fclose($this->fd);
                $this->html = $this->plantilla;
                if (file_exists($this->programa)) {
                    $this->datos = array_merge(
                        aislarInclusion($this->programa, $valores),
                        $this->datos
                    );
                }
            } else {
                $this->html = "Error - No existe el archivo invocado";
            }

            $this->procesar();
        }

        private function mostrarVariable($CONTENIDO) {
            return isset($this->datos[$CONTENIDO])
                ? $this->datos[$CONTENIDO]
                : "{$CONTENIDO}";
        }

        private function envolver($ELEMENTO) {
            $this->pila[] = $this->datos;
            foreach ($ELEMENTO as $indice => $valor) {
                $this->datos[$indice] = $valor;
            }
        }

        private function desenvolver() {
            $this->datos = array_pop($this->pila);
        }

        private function obtenerSubelemento($plantilla, $datos = [0 => ""]) {
            $subplantilla = new Plantilla($plantilla, $datos);
            $subplantilla->procesar();
            $subplantilla->ejecutarPhp();
            echo $subplantilla->html;
        }

        private function ejecutarPhp() {
            $archivoTemporal = tempnam(
                sys_get_temp_dir(),
                substr($this->archivo, 0, -4) . "-"
            );
            $temporal = fopen($archivoTemporal, "w");
            fwrite($temporal, $this->html);
            ob_start();
            include "$archivoTemporal";
            $this->html = ob_get_contents();
            ob_end_clean();
            fclose($temporal);
            unlink($archivoTemporal);
        }

        private function procesar($datos = [0 => ""]) {
            $this->datos = array_merge($this->datos, $datos);
            $this->pila = [];

            $this->html = preg_replace(
                "#\{(\w+)\}#",
                //'/{(\w+)}/' ,
                '<?php echo $this->mostrarVariable( \'$1\' ) ; ?>',
                $this->html
            );
            $this->html = preg_replace(
                "#\{VECTOR:([^}]*)\}#",
                '<?php if( is_array( $this->mostrarVariable( \'$1\' ) ) ) { echo implode( "," , $this->mostrarVariable( \'$1\' ) ) ; } else { echo \'$1\' ; } ?> ',
                $this->html
            );
            $this->html = preg_replace(
                "#\{PLANTILLA:([^}]*)\}#",
                '<?php $this->obtenerSubelemento( \'$1\' , $this->datos ) ; ?> ',
                $this->html
            );
            $this->html = preg_replace(
                "#\{BUCLE:(\w+)\}#",
                '<?php if ( isset( $this->datos[ \'$1\' ] ) ) { foreach ( $this->datos[ \'$1\' ] as $ELEMENT ): $this->envolver( $ELEMENT ); ?> ',
                $this->html
            );
            $this->html = preg_replace(
                "#\{BUCLEFIN:(\w+)\}#",
                '<?php $this->desenvolver( ); endforeach; } ?> ',
                /*
                    # Alternativa 1
                    '<?php $this->desenvolver( ); endforeach; } else { echo "{BUCLE:".$this->mostrarVariable( \'$1\' )."}" ; } ?> ' ,
                    # Alternativa 2
                    '<?php $this->desenvolver( ); endforeach; } else {  } ?>' ,
                    */
                $this->html
            );
            $this->ejecutarPhp();
        }

        public function mostrar() {
            echo $this->html;
        }
    }

    /*
        Clase para el manejo práctico de conexiones a base de datos SQLite, mariadb y postgresql
    */
    class Conexion {
        private $enlace;
        private $error;
        public  $estado;

        function __construct($configuracion = "") {
            if (!$configuracion) {
                $configuracion = "base.php";
            }
            include "$configuracion";
            $dsn = "";
            switch ($TIPO) {
                case "sqlite3":
                    $ARCHIVO = $ARCHIVO ? $ARCHIVO : "base.sqlite3";
                    if (file_exists($ARCHIVO) && is_writable($ARCHIVO)) {
                        $dsn = "sqlite:" . $ARCHIVO;
                    } else {
                        $this->estado = false;
                        $this->error = "No existe o no se puede escribir el archivo : $ARCHIVO";
                    }
                    break;
                case "mariadb":
                case "mysql":
                    $dsn = "mysql:host=$SERVIDOR;port=$PUERTO;dbname=$BASE;charset=utf8";
                    break;
                case "postgresql":
                    $dsn = "pgsql:host=$SERVIDOR;port=$PUERTO;dbname=$BASE";
                    break;
                default:
                    $this->estado = false;
                    $this->error = "No se identificó el tipo de base de datos en la configuración : $configuracion";
                    break;
            }
            try {
                $this->estado = $this->conectar($TIPO, $dsn, $USUARIO, $CLAVE);
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }

        private function conectar($tipo, $dsn, $usuario, $clave) {
            $this->error = false;
            try {
                $this->enlace =
                    $tipo == "sqlite3"
                        ? @new PDO($dsn)
                        : @new PDO($dsn, $usuario, $clave);
                $this->enlace->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
                $this->enlace->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $this->enlace->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );
                if ($tipo == "sqlite3") {
                    $this->enlace->exec("PRAGMA foreign_keys = ON;");
                }
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
            return !$this->error;
        }

        public function consultar($sql, $datos = []) {
            $resultado = [];
            if ($this->estado == 1) {
                $this->error = false;
                try {
                    $sentencia = $this->enlace->prepare($sql);
                    if ($sentencia->execute($datos)) {
                        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $this->error = true;
                    }
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                }
            }
            return $resultado;
        }

        public function ejecutar($sql, $datos = []) {
            $resultado = false;
            if ($this->estado == 1) {
                $this->error = false;
                try {
                    $sentencia = $this->enlace->prepare($sql);
                    if ($sentencia->execute($datos)) {
                        $resultado = true;
                    } else {
                        $this->error = true;
                    }
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                }
            }
            return $resultado;
        }

        public function ultimoId() {
            return $this->estado == 1 ? $this->enlace->lastInsertId() : "";
        }

        public function obtenerError() {
            return $this->error;
        }
    }

    /**
     *  Funciones
     *
     *  Conjunto de posibles funciones para facilitar el desarrollo de aplicaciones
     **/

    // Chiripazo serendipezco olímpico para aislar datos en la clase "plantilla"
    function aislarInclusion($programa, $traspaso) {
        // Precargando el vector de datos para el programa a incluir
        $_P = $traspaso;
        $_D = [];
        // Incluyendo el archivo para procesar el vector
        include "$programa";
        // Recuperando el vector procesado
        return $_D;
    }

    function mostrarVector($vector = [], $clase = "") {
        echo "<pre class='$clase'>" . print_r($vector, true) . "</pre>";
    }

    function paraMostrar($vector = [], $clase = "") {
        return "<pre class='$clase'>" . print_r($vector, true) . "</pre>";
    }

    function mostrarAlgo($algo, $clase = "") {
        echo "<pre class='$clase'>" ;
        var_dump($algo)             ;
        echo "</pre>"               ;
    }

    function vectorParaBucle($vector = [], $nombre = "valor") {
        $nuevo = [];
        foreach ($vector as $llave => $valor) {
            $nuevo[]["$nombre"] = $valor;
        }
        return $nuevo;
    }

    function deConsultaAVector($vector = []) {
        $nuevo = [];
        $uno = true;
        foreach ($vector as $registro) {
            if ($uno) {
                $primero = [];
                foreach ($registro as $campo => $valor) {
                    $primero[] = $campo;
                }
                $nuevo[] = $primero;
            }
            $nuevo[] = $registro;
        }
        return $nuevo;
    }

    function llave($cantidad = 4) {
        $valores = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $llave = "";
        $longitud = strlen($valores);
        for ($i = 0; $i < $cantidad; $i++) {
            $llave = $llave . $valores[rand(0, $longitud - 1)];
        }
        return $llave;
    }

    function llaveM($cantidad = 4) {
        $valores = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $llave = "";
        $longitud = strlen($valores);
        for ($i = 0; $i < $cantidad; $i++) {
            $llave = $llave . $valores[rand(0, $longitud - 1)];
        }
        return $llave;
    }

    function minuscular($s, $i = false) {
        // $s : cadena de texto string
        // $i : invertir
        // $m : lista de letras minúsculas
        // $M : lista de letras mayúsculas
        $m = [
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t",
            "u", "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í",
            "î", "ï", "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д",
            "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч",
            "ш", "щ", "ъ", "ы", "ь", "э", "ю", "я",
        ];
        $M = [
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T",
            "U", "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í",
            "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д",
            "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч",
            "Ш", "Щ", "Ъ", "Ъ", "Ь", "Э", "Ю", "Я",
        ];
        return $i ? str_replace($m, $M, $s) : str_replace($M, $m, $s);
    }

    function obtenerTiempo($c = true) {
        // $c : completo
        // $F : fecha
        // $V : valor de iteración
        // obtenerTiempo() - > 2021-01-23 12:10:37
        // obtenerTiempo( false ) -> 2021-01-23
        $F = getdate();
        foreach (["mon", "mday", "hours", "minutes", "seconds"] as $V) {
            if ($F[$V] < 10) {
                $F[$V] = "0" . $F[$V];
            }
        }
        return $c
            ? "$F[year]-$F[mon]-$F[mday] $F[hours]:$F[minutes]:$F[seconds]"
            : "$F[year]-$F[mon]-$F[mday]";
    }

    function cargarTpl($p, $v = []) {
        // $p : plantilla
        // $v : vector de datos asociados
        // $C : Contenido a Cargar
        // $a : archivo
        // $d : directorio
        $C = "";
        if (file_exists($p)) {
            $C = @new Plantilla($p, $v);
            $C = @$C->html;
        } else {
            $a = basename($p, ".tpl");
            $d = explode("/", dirname($p));
            $d = $d[count($d) - 1];
            $C = "No existe [$a] en [$d]<script>setTimeout(function () { location.reload(true); }, 2000);</script>";
        }
        return $C;
    }

    // Manejo de peticiones REQUEST
    function _VIENE($i, $v = "") {
        // $i : indice
        // $v : valor
        return $_REQUEST[$i] ?? $v;
    }

    // Intento de limpieza de caracteres prohibidos
    function limpiar(string $t) {
        // $t : texto
        // $n : vector de valores no validos
        $n = [".", "/", "index", "\\"];
        return strlen($t) > 0 && in_array(substr($t, 0), [".", "/", "index", "\\"])
            ? limpiar(substr($t, 1, strlen($t) - 1))
            : //return str_replace( "\\" , "/" , $t );
            //return str_replace( [ "." , "/" , "index" ] , "" , str_replace( "\\" , "/" , $t ) ) ;
            str_replace("\\", "/", str_replace([".", "/", "index"], "", $t));
    }

    // Envolvedor de las dos anteriores
    function _LIMPIA ( $i , $v = "" ) {
        // $i : indice
        // $v : valor
        return limpiar( $_REQUEST[$i] ?? $v ) ;
    }

    // Manejo de sesiones
    
    function miHash() {
        // h : hash
        $_SESSION["h"] = md5(getcwd());
        // return md5( getcwd() ) ;
    }
    
    function elHash() {
        // h : hash
        return $_SESSION["h"];
    }
    
    function _VER($identificador) {
        return $_SESSION[$_SESSION["h"] . "_i"][$identificador] ?? null;
    }

    function _DAR($identificador, $valor) {
        $_SESSION[$_SESSION["h"] . "_i"][$identificador] = $valor;
    }

    function _BOTAR($identificador) {
        unset($_SESSION[$_SESSION["h"] . "_i"][$identificador]);
    }

    // control de sesiones particulares para identificadores del sistema
    function ingresado() {
        // i : estado de ingresado
        return isset($_SESSION[$_SESSION["h"] . "_i"]["ID"]);
    }

    // control de sesiones particulares para identificadores del root
    function esRoot() {
        // root: es el root
        return isset($_SESSION[$_SESSION["h"] . "_i"]["ID"]) &&
            $_SESSION[$_SESSION["h"] . "_i"]["ID"] == 0;
    }

    function obtenerArchivoSubido($campo, $ruta) {
        $PESO = $_FILES[$campo]["size"];
        $TIPO = $_FILES[$campo]["type"];
        $NOMBRE = $_FILES[$campo]["name"];
        $ubicacion = $ruta . $NOMBRE;
        # unlink( $ubicacion ) ;
        return @move_uploaded_file($_FILES[$campo]["tmp_name"], $ubicacion)
            ? $ubicacion
            : false;
    }

    function leerArchivo($ruta) {
        $contenido = "";
        if (file_exists($ruta)) {
            $archivo = fopen($ruta, "r");
            while (!feof($archivo)) {
                $contenido .= fgets($archivo);
            }
            fclose($archivo);
        }
        return $contenido;
    }

    function escribirArchivo($ruta, $contenido) {
        $retorno = false;
        if (is_writable($ruta)) {
            $archivo = fopen($ruta, "w");
            if (fwrite($archivo, $contenido) !== false) {
                $retorno = true;
            }
        }
        return $retorno;
    }

    function arregloAVariables($a = []) {
        $n = "";
        if (!empty($a) && is_array($a)) {
            foreach ($a as $k => $v) {
                $n .= $k . "=" . $v . "&";
            }
            return substr($n, 0, strlen($n) - 1);
        } else {
            return $n;
        }
    }

    // convertir archivo de imagen a base64
    function convertirBase64($imagen) {
        $base64 = "";
        if (file_exists($imagen) && is_readable($imagen)) {
            $base64 = base64_encode(file_get_contents($imagen));
        }
        return $base64;
    }

?>
