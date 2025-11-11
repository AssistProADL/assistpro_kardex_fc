-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 11-11-2025 a las 07:08:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `assistpro_etl_fc`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacoratiempos`
--

CREATE TABLE `bitacoratiempos` (
  `Id` int(11) NOT NULL,
  `Codigo` varchar(50) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `HI` datetime DEFAULT NULL,
  `HF` datetime DEFAULT NULL,
  `HT` varchar(20) DEFAULT NULL,
  `TS` varchar(20) DEFAULT NULL,
  `Visita` bit(1) DEFAULT NULL,
  `Programado` bit(1) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `Cerrado` bit(1) DEFAULT NULL,
  `IdV` int(11) DEFAULT NULL,
  `Tip` varchar(10) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `pila` tinyint(4) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `IdVendedor` int(11) DEFAULT NULL,
  `Id_Ayudante1` int(11) DEFAULT NULL,
  `Id_Ayudante2` int(11) DEFAULT NULL,
  `IdVehiculo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cab_planifica_inventario`
--

CREATE TABLE `cab_planifica_inventario` (
  `ID_PLAN` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `ID_PERIODO` int(11) DEFAULT NULL,
  `DESCRIPCION` varchar(250) DEFAULT NULL,
  `FECHA_INI` datetime DEFAULT NULL,
  `FECHA_FIN` datetime DEFAULT NULL,
  `INTERVALO` int(11) DEFAULT NULL,
  `ID_EXCALAR` int(11) DEFAULT NULL,
  `DIA_MES` int(11) DEFAULT NULL,
  `MES_YEAR` int(11) DEFAULT NULL,
  `DIAS_LABORABLES` char(1) DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_dias_festivos`
--

CREATE TABLE `cat_dias_festivos` (
  `ID_FESTIVO` int(11) NOT NULL,
  `FECHA` datetime DEFAULT NULL,
  `DESCRIPCION` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_dia_semana`
--

CREATE TABLE `cat_dia_semana` (
  `ID_DIA` int(11) NOT NULL,
  `DIA_DESCRIPCION` varchar(20) DEFAULT NULL,
  `LABORABLE` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_escalares`
--

CREATE TABLE `cat_escalares` (
  `ID_ESCALAR` int(11) NOT NULL,
  `DESCRIPCION` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_estados`
--

CREATE TABLE `cat_estados` (
  `ESTADO` char(1) NOT NULL,
  `DESCRIPCION` char(100) DEFAULT NULL,
  `DURACION` int(11) DEFAULT NULL,
  `ORDEN` int(11) DEFAULT NULL,
  `COLORR` int(11) DEFAULT NULL,
  `COLORG` int(11) DEFAULT NULL,
  `COLORB` int(11) DEFAULT NULL,
  `STATUS` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_tipos_periodos`
--

CREATE TABLE `cat_tipos_periodos` (
  `ID_PERIODO` int(11) NOT NULL,
  `DESCRIPCION` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cobranza`
--

CREATE TABLE `cobranza` (
  `id` int(11) NOT NULL,
  `Cliente` int(11) DEFAULT NULL,
  `Documento` varchar(50) DEFAULT NULL,
  `Saldo` decimal(10,0) DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `UltPago` varchar(50) DEFAULT NULL,
  `FechaReg` datetime DEFAULT NULL,
  `FechaVence` datetime DEFAULT NULL,
  `FolioInterno` int(11) DEFAULT NULL,
  `TipoDoc` varchar(50) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codesop`
--

CREATE TABLE `codesop` (
  `Codi` varchar(50) NOT NULL,
  `Operacion` varchar(255) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `EsRecarga` tinyint(1) DEFAULT NULL,
  `Gasto` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colaprocesamiento`
--

CREATE TABLE `colaprocesamiento` (
  `id` int(11) NOT NULL,
  `idVendedor` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `idEmpresa` varchar(50) DEFAULT NULL,
  `nombreArchivo` varchar(500) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Procesado` varchar(50) DEFAULT NULL,
  `FechaProcesado` datetime DEFAULT NULL,
  `idRuta` int(11) DEFAULT NULL,
  `rutaOpcional` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configrutasp`
--

CREATE TABLE `configrutasp` (
  `Id` int(11) NOT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `ModelPrinter` varchar(50) DEFAULT NULL,
  `VelCom` varchar(50) DEFAULT NULL,
  `COM` varchar(50) DEFAULT NULL,
  `Server` varchar(255) DEFAULT NULL,
  `Puerto` int(11) DEFAULT NULL,
  `ServerGPS` varchar(50) DEFAULT NULL,
  `GPS` smallint(6) DEFAULT NULL,
  `PuertoG` varchar(50) DEFAULT NULL,
  `PagoContado` smallint(6) DEFAULT NULL,
  `CteNvo` smallint(6) DEFAULT NULL,
  `CveCteNvo` smallint(6) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `SugerirCant` smallint(6) DEFAULT NULL,
  `PromoEq` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contadores_mensuales`
--

CREATE TABLE `contadores_mensuales` (
  `prefijo` varchar(10) NOT NULL,
  `ultimo_valor` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contadores_prefijos`
--

CREATE TABLE `contadores_prefijos` (
  `prefijo` varchar(10) NOT NULL,
  `ultimo_valor` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `continuidad`
--

CREATE TABLE `continuidad` (
  `RutaID` int(11) NOT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `FolVta` int(11) DEFAULT NULL,
  `FolPed` int(11) DEFAULT NULL,
  `FolDevol` int(11) DEFAULT NULL,
  `FolCob` int(11) DEFAULT NULL,
  `UDiaO` int(11) DEFAULT NULL,
  `CteNvo` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `FolGto` int(11) DEFAULT NULL,
  `FolServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_authassignment`
--

CREATE TABLE `cruge_authassignment` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `bizrule` longtext DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `itemname` varchar(64) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_authitem`
--

CREATE TABLE `cruge_authitem` (
  `id` int(11) NOT NULL,
  `name` varchar(64) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `description` longtext DEFAULT NULL,
  `bizrule` longtext DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_authitemchild`
--

CREATE TABLE `cruge_authitemchild` (
  `id` int(11) NOT NULL,
  `parent` varchar(64) DEFAULT NULL,
  `child` varchar(64) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_field`
--

CREATE TABLE `cruge_field` (
  `idfield` int(11) NOT NULL,
  `fieldname` varchar(20) DEFAULT NULL,
  `longname` varchar(50) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `required` int(11) DEFAULT NULL,
  `fieldtype` int(11) DEFAULT NULL,
  `fieldsize` int(11) DEFAULT NULL,
  `maxlength` int(11) DEFAULT NULL,
  `showinreports` int(11) DEFAULT NULL,
  `useregexp` text DEFAULT NULL,
  `useregexpmsg` text DEFAULT NULL,
  `predetvalue` longtext DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_fieldvalue`
--

CREATE TABLE `cruge_fieldvalue` (
  `idfieldvalue` int(11) NOT NULL,
  `iduser` int(11) NOT NULL,
  `idfield` int(11) NOT NULL,
  `value` longtext DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_session`
--

CREATE TABLE `cruge_session` (
  `idsession` int(11) NOT NULL,
  `iduser` int(11) NOT NULL,
  `created` bigint(20) DEFAULT NULL,
  `expire` bigint(20) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `ipaddress` varchar(45) DEFAULT NULL,
  `usagecount` int(11) DEFAULT NULL,
  `lastusage` bigint(20) DEFAULT NULL,
  `logoutdate` bigint(20) DEFAULT NULL,
  `ipaddressout` varchar(45) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_system`
--

CREATE TABLE `cruge_system` (
  `name` varchar(45) DEFAULT NULL,
  `largename` varchar(45) DEFAULT NULL,
  `sessionmaxdurationmins` int(11) DEFAULT NULL,
  `sessionmaxsameipconnections` int(11) DEFAULT NULL,
  `sessionreusesessions` int(11) DEFAULT NULL,
  `sessionmaxsessionsperday` int(11) DEFAULT NULL,
  `sessionmaxsessionsperuser` int(11) DEFAULT NULL,
  `systemnonewsessions` int(11) DEFAULT NULL,
  `systemdown` int(11) DEFAULT NULL,
  `registerusingcaptcha` int(11) DEFAULT NULL,
  `registerusingterms` int(11) DEFAULT NULL,
  `terms` longblob DEFAULT NULL,
  `registerusingactivation` int(11) DEFAULT NULL,
  `defaultroleforregistration` varchar(64) DEFAULT NULL,
  `registerusingtermslabel` varchar(100) DEFAULT NULL,
  `registrationonlogin` int(11) DEFAULT NULL,
  `idsystem` int(11) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cruge_user`
--

CREATE TABLE `cruge_user` (
  `iduser` int(11) NOT NULL,
  `regdate` bigint(20) DEFAULT NULL,
  `actdate` bigint(20) DEFAULT NULL,
  `logondate` bigint(20) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `authkey` varchar(100) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `totalsessioncounter` int(11) DEFAULT NULL,
  `currentsessioncounter` int(11) DEFAULT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `image_profile` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ctiket`
--

CREATE TABLE `ctiket` (
  `ID` int(11) NOT NULL,
  `Linea1` varchar(255) DEFAULT NULL,
  `Linea2` varchar(255) DEFAULT NULL,
  `Linea3` varchar(255) DEFAULT NULL,
  `Linea4` varchar(255) DEFAULT NULL,
  `Mensaje` varchar(255) DEFAULT NULL,
  `Tdv` smallint(6) DEFAULT NULL,
  `LOGO` blob DEFAULT NULL,
  `MLiq` smallint(6) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuotas`
--

CREATE TABLE `cuotas` (
  `Id` int(11) NOT NULL,
  `Clave` varchar(50) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `UniMed` varchar(50) DEFAULT NULL,
  `Cantidad` varchar(50) DEFAULT NULL,
  `FechaI` datetime DEFAULT NULL,
  `FechaF` datetime DEFAULT NULL,
  `Producto` varchar(50) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `Activa` tinyint(1) DEFAULT NULL,
  `NivelNum` tinyint(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_aduanas`
--

CREATE TABLE `c_aduanas` (
  `Id_CatAduana` int(11) NOT NULL,
  `Cve_CatAduana` varchar(50) DEFAULT NULL,
  `Des_CatAduana` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_almacen`
--

CREATE TABLE `c_almacen` (
  `cve_almac` int(11) NOT NULL,
  `clave_almacen` varchar(20) DEFAULT NULL,
  `cve_almacenp` int(11) NOT NULL,
  `des_almac` varchar(150) DEFAULT NULL,
  `des_direcc` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Cve_TipoZona` varchar(50) DEFAULT NULL,
  `clasif_abc` char(1) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_almacenp`
--

CREATE TABLE `c_almacenp` (
  `id` int(11) NOT NULL,
  `clave` varchar(20) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `rut` varchar(255) DEFAULT NULL,
  `codigopostal` int(11) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `comentarios` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `distrito` varchar(255) DEFAULT NULL,
  `cve_talmacen` int(11) NOT NULL,
  `No_Licencias` int(11) DEFAULT NULL,
  `cve_cia` int(11) DEFAULT NULL,
  `BL` varchar(50) DEFAULT NULL,
  `BL_Pasillo` tinyint(4) DEFAULT NULL,
  `BL_Rack` tinyint(4) DEFAULT NULL,
  `BL_Nivel` tinyint(4) DEFAULT NULL,
  `BL_Seccion` tinyint(4) DEFAULT NULL,
  `BL_Posicion` tinyint(4) DEFAULT NULL,
  `longitud` decimal(18,10) DEFAULT NULL,
  `latitud` decimal(18,10) DEFAULT NULL,
  `interno` int(11) DEFAULT NULL,
  `tipolp_traslado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_articulo`
--

CREATE TABLE `c_articulo` (
  `cve_articulo` varchar(55) DEFAULT NULL,
  `des_articulo` varchar(100) DEFAULT NULL,
  `des_detallada` text DEFAULT NULL,
  `cve_umed` int(11) DEFAULT NULL,
  `cve_ssgpo` int(11) DEFAULT NULL,
  `fec_altaart` datetime DEFAULT NULL,
  `imp_costo` decimal(19,4) DEFAULT NULL,
  `des_tipo` varchar(20) DEFAULT NULL,
  `comp_cveumed` int(11) DEFAULT NULL,
  `empq_cveumed` int(11) DEFAULT NULL,
  `num_multiplo` double DEFAULT NULL,
  `des_observ` varchar(255) DEFAULT NULL,
  `mav_almacenable` varchar(1) DEFAULT NULL,
  `cve_moneda` int(11) DEFAULT NULL,
  `cve_almac` int(11) NOT NULL,
  `mav_cveubica` varchar(20) DEFAULT NULL,
  `mav_delinea` varchar(1) DEFAULT NULL,
  `mav_obsoleto` varchar(1) DEFAULT NULL,
  `mav_pctiva` decimal(19,4) DEFAULT NULL,
  `IEPS` decimal(19,4) DEFAULT NULL,
  `PrecioVenta` decimal(19,4) DEFAULT NULL,
  `cve_tipcaja` int(11) DEFAULT NULL,
  `ban_condic` tinyint(4) DEFAULT NULL,
  `num_volxpal` decimal(19,4) DEFAULT NULL,
  `cve_codprov` varchar(20) DEFAULT NULL,
  `remplazo` varchar(20) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `peso` double DEFAULT NULL,
  `num_multiploch` int(11) DEFAULT NULL,
  `barras2` varchar(20) DEFAULT NULL,
  `Caduca` char(1) DEFAULT NULL,
  `Compuesto` char(1) DEFAULT NULL,
  `Max_Cajas` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `barras3` varchar(20) DEFAULT NULL,
  `cajas_palet` int(11) DEFAULT NULL,
  `control_lotes` char(1) DEFAULT NULL,
  `control_numero_series` char(1) DEFAULT NULL,
  `control_peso` char(1) DEFAULT NULL,
  `control_volumen` char(1) DEFAULT NULL,
  `req_refrigeracion` char(1) DEFAULT NULL,
  `mat_peligroso` char(1) DEFAULT NULL,
  `grupo` varchar(20) DEFAULT NULL,
  `clasificacion` varchar(20) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `tipo_caja` int(11) DEFAULT NULL,
  `alto` decimal(10,2) DEFAULT NULL,
  `fondo` decimal(10,2) DEFAULT NULL,
  `ancho` decimal(10,2) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `tipo_producto` varchar(30) DEFAULT NULL,
  `umas` int(11) DEFAULT NULL,
  `unidadMedida` int(11) DEFAULT NULL,
  `costoPromedio` double(10,2) DEFAULT NULL,
  `Cve_SAP` varchar(50) DEFAULT NULL,
  `Ban_Envase` char(1) DEFAULT NULL,
  `Usa_Envase` char(1) DEFAULT NULL,
  `Tipo_Envase` char(1) DEFAULT NULL,
  `control_abc` char(1) DEFAULT NULL,
  `cve_alt` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_articulo_codigo`
--

CREATE TABLE `c_articulo_codigo` (
  `Cve_Almacen` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Clte` varchar(50) NOT NULL,
  `Codigo` varchar(50) DEFAULT NULL,
  `Sku_R` varchar(50) DEFAULT NULL,
  `Descripcion` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_articulo_documento`
--

CREATE TABLE `c_articulo_documento` (
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `ruta` varchar(200) DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `documento` blob NOT NULL,
  `TYPE` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_articulo_ext`
--

CREATE TABLE `c_articulo_ext` (
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_articulo_imagen`
--

CREATE TABLE `c_articulo_imagen` (
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_caracteristicas`
--

CREATE TABLE `c_caracteristicas` (
  `Id_Carac` int(11) NOT NULL,
  `Cve_Carac` varchar(50) DEFAULT NULL,
  `Des_Carac` varchar(255) DEFAULT NULL,
  `Id_Tipo_car` int(11) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_centrocostos`
--

CREATE TABLE `c_centrocostos` (
  `Id_CC` int(11) NOT NULL,
  `Cve_Cia` int(11) NOT NULL,
  `Cve_CC` varchar(50) NOT NULL,
  `Desc_CC` varchar(250) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_charolas`
--

CREATE TABLE `c_charolas` (
  `IDContenedor` int(11) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Clave_Contenedor` varchar(70) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `Permanente` tinyint(1) DEFAULT NULL,
  `Pedido` varchar(20) DEFAULT NULL,
  `sufijo` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `alto` int(11) DEFAULT NULL,
  `ancho` int(11) DEFAULT NULL,
  `fondo` int(11) DEFAULT NULL,
  `peso` decimal(18,3) DEFAULT NULL,
  `pesomax` decimal(18,3) DEFAULT NULL,
  `capavol` decimal(18,3) DEFAULT NULL,
  `Costo` decimal(18,3) DEFAULT NULL,
  `CveLP` varchar(70) DEFAULT NULL,
  `TipoGen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_cliente`
--

CREATE TABLE `c_cliente` (
  `id_cliente` int(11) NOT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `RazonSocial` varchar(100) DEFAULT NULL,
  `RazonComercial` varchar(100) DEFAULT NULL,
  `CalleNumero` varchar(100) DEFAULT NULL,
  `Colonia` varchar(100) DEFAULT NULL,
  `Ciudad` varchar(50) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Pais` varchar(50) DEFAULT NULL,
  `CodigoPostal` varchar(100) DEFAULT NULL,
  `RFC` varchar(100) DEFAULT NULL,
  `Telefono1` varchar(100) DEFAULT NULL,
  `Telefono2` varchar(100) DEFAULT NULL,
  `Telefono3` varchar(100) DEFAULT NULL,
  `ClienteTipo` varchar(20) DEFAULT NULL,
  `ClienteTipo2` varchar(20) DEFAULT NULL,
  `ClienteGrupo` varchar(20) DEFAULT NULL,
  `ClienteFamilia` varchar(20) DEFAULT NULL,
  `CondicionPago` varchar(30) DEFAULT NULL,
  `MedioEmbarque` varchar(20) DEFAULT NULL,
  `ViaEmbarque` varchar(20) DEFAULT NULL,
  `CondicionEmbarque` varchar(20) DEFAULT NULL,
  `ZonaVenta` varchar(20) DEFAULT NULL,
  `cve_ruta` varchar(20) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Cve_Almacenp` int(11) DEFAULT NULL,
  `Fol_Serie` int(11) DEFAULT NULL,
  `Contacto` varchar(255) DEFAULT NULL,
  `id_destinatario` int(11) DEFAULT NULL,
  `longitud` text DEFAULT NULL,
  `latitud` text DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `email_cliente` varchar(500) DEFAULT NULL,
  `Cve_SAP` varchar(50) DEFAULT NULL,
  `Encargado` varchar(200) DEFAULT NULL,
  `Referencia` varchar(200) DEFAULT NULL,
  `credito` int(11) DEFAULT NULL,
  `limite_credito` float(18,2) DEFAULT NULL,
  `dias_credito` int(11) DEFAULT NULL,
  `credito_actual` float(18,2) DEFAULT NULL,
  `saldo_inicial` float(18,2) DEFAULT NULL,
  `saldo_actual` float(18,2) DEFAULT NULL,
  `validar_gps` int(11) DEFAULT NULL,
  `cliente_general` int(11) DEFAULT NULL,
  `Id_RegFis` int(11) DEFAULT NULL,
  `Id_CFDI` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_compania`
--

CREATE TABLE `c_compania` (
  `cve_cia` int(11) NOT NULL,
  `clave_empresa` varchar(255) DEFAULT NULL,
  `distrito` varchar(255) DEFAULT NULL,
  `cve_tipcia` int(11) NOT NULL,
  `des_cia` varchar(100) DEFAULT NULL,
  `des_rfc` varchar(20) DEFAULT NULL,
  `des_direcc` varchar(150) DEFAULT NULL,
  `des_cp` varchar(10) DEFAULT NULL,
  `des_telef` varchar(50) DEFAULT NULL,
  `des_contacto` varchar(100) DEFAULT NULL,
  `des_email` varchar(100) DEFAULT NULL,
  `des_observ` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `longitud` float DEFAULT NULL,
  `latitud` float DEFAULT NULL,
  `Es_3PL` char(1) DEFAULT NULL,
  `Id_Proveedor` int(11) DEFAULT NULL,
  `es_transportista` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_contactos`
--

CREATE TABLE `c_contactos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono1` varchar(50) DEFAULT NULL,
  `telefono2` varchar(50) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_dane`
--

CREATE TABLE `c_dane` (
  `id_dane` int(11) NOT NULL,
  `cod_municipio` int(11) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `des_municipio` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_datos_sap`
--

CREATE TABLE `c_datos_sap` (
  `Id` int(11) NOT NULL,
  `Url` varchar(255) DEFAULT NULL,
  `User` varchar(50) DEFAULT NULL,
  `Pswd` varchar(50) DEFAULT NULL,
  `BaseD` varchar(50) DEFAULT NULL,
  `Empresa` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_datos_ws`
--

CREATE TABLE `c_datos_ws` (
  `Id` int(11) NOT NULL,
  `Url` varchar(255) DEFAULT NULL,
  `Puerto` int(11) DEFAULT NULL,
  `Servicio` varchar(255) DEFAULT NULL,
  `User` varchar(50) DEFAULT NULL,
  `Pswd` varchar(50) DEFAULT NULL,
  `Empresa` varchar(50) DEFAULT NULL,
  `Token` varchar(800) DEFAULT NULL,
  `RefresH_Token` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_departamento`
--

CREATE TABLE `c_departamento` (
  `Id_Dep` int(11) NOT NULL,
  `Cve_Dep` varchar(50) NOT NULL,
  `Des_Dep` varchar(50) NOT NULL,
  `Id_CC` int(11) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_destinatarios`
--

CREATE TABLE `c_destinatarios` (
  `id_destinatario` int(11) NOT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `razonsocial` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `postal` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `Activo` enum('0','1') NOT NULL,
  `clave_destinatario` varchar(20) DEFAULT NULL,
  `cve_vendedor` varchar(50) DEFAULT NULL,
  `email_destinatario` varchar(50) DEFAULT NULL,
  `latitud` text DEFAULT NULL,
  `longitud` text DEFAULT NULL,
  `dir_principal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_devclientes`
--

CREATE TABLE `c_devclientes` (
  `id` int(11) NOT NULL,
  `folio_dev` int(11) NOT NULL,
  `folio_pedido` varchar(50) DEFAULT NULL,
  `folio_entrada` varchar(50) DEFAULT NULL,
  `cve_articulo` varchar(100) DEFAULT NULL,
  `cve_lote` varchar(10) DEFAULT NULL,
  `caducidad` date DEFAULT NULL,
  `cantidad_devuelta` float NOT NULL,
  `zona_recepcion` varchar(20) DEFAULT NULL,
  `cve_contenedor` varchar(50) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `defectuoso` int(11) DEFAULT NULL,
  `motivo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_devproveedores`
--

CREATE TABLE `c_devproveedores` (
  `id` int(11) NOT NULL,
  `folio_dev` varchar(20) DEFAULT NULL,
  `folio_entrada` varchar(50) DEFAULT NULL,
  `cve_contenedor` varchar(50) DEFAULT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(20) DEFAULT NULL,
  `caducidad` date DEFAULT NULL,
  `devueltas` double DEFAULT NULL,
  `idy_ubica` varchar(20) DEFAULT NULL,
  `proveedor` int(11) DEFAULT NULL,
  `factura` varchar(50) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `defectuoso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_embarque_documentos`
--

CREATE TABLE `c_embarque_documentos` (
  `id` int(11) NOT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `ruta` varchar(200) DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `documento` blob NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `tipo` enum('E','P') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_error`
--

CREATE TABLE `c_error` (
  `Clave` varchar(10) NOT NULL,
  `Descripcion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_estado`
--

CREATE TABLE `c_estado` (
  `cve_estado` int(11) NOT NULL,
  `des_estado` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_gpoarticulo`
--

CREATE TABLE `c_gpoarticulo` (
  `id` int(11) NOT NULL,
  `cve_gpoart` varchar(20) DEFAULT NULL,
  `des_gpoart` varchar(100) DEFAULT NULL,
  `por_depcont` double DEFAULT NULL,
  `por_depfical` double DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_gpoclientes`
--

CREATE TABLE `c_gpoclientes` (
  `id` int(11) NOT NULL,
  `cve_grupo` varchar(50) DEFAULT NULL,
  `des_grupo` varchar(200) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_gposervicios`
--

CREATE TABLE `c_gposervicios` (
  `Id_GpoServicio` int(11) NOT NULL,
  `Cve_GpoServicio` varchar(50) NOT NULL,
  `Des_GpoServicio` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_lotes`
--

CREATE TABLE `c_lotes` (
  `id` int(10) UNSIGNED NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `Lote` varchar(50) DEFAULT NULL,
  `Caducidad` date NOT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Fec_Prod` date DEFAULT NULL,
  `Lote_Alterno` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_monedas`
--

CREATE TABLE `c_monedas` (
  `Id_Moneda` int(11) NOT NULL,
  `Cve_Moneda` varchar(50) NOT NULL,
  `Des_Moneda` varchar(250) DEFAULT NULL,
  `Ban_M_Nacional` varchar(1) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_motivo`
--

CREATE TABLE `c_motivo` (
  `id` int(11) NOT NULL,
  `Tipo_Cat` char(1) DEFAULT NULL,
  `Des_Motivo` varchar(50) DEFAULT NULL,
  `dev_proveedor` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_palletsdevueltos`
--

CREATE TABLE `c_palletsdevueltos` (
  `ID` int(11) NOT NULL,
  `cve_almac` varchar(20) DEFAULT NULL,
  `descripcion` varchar(50) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `clave` varchar(30) DEFAULT NULL,
  `ClaveLP` varchar(20) DEFAULT NULL,
  `desc_almac` varchar(100) DEFAULT NULL,
  `statu` varchar(50) DEFAULT NULL,
  `pedido` varchar(20) DEFAULT NULL,
  `razon` varchar(100) DEFAULT NULL,
  `cliente` varchar(50) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `fecha` varchar(20) DEFAULT NULL,
  `fechadev` datetime DEFAULT NULL,
  `dias` varchar(20) DEFAULT NULL,
  `bl` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_poblacion`
--

CREATE TABLE `c_poblacion` (
  `cve_pobla` int(11) NOT NULL,
  `cve_estado` int(11) NOT NULL,
  `des_pobla` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_presupuestos`
--

CREATE TABLE `c_presupuestos` (
  `id` int(11) NOT NULL,
  `nombreDePresupuesto` varchar(100) DEFAULT NULL,
  `anoDePresupuesto` int(11) NOT NULL,
  `claveDePartida` varchar(30) DEFAULT NULL,
  `conceptoDePartida` varchar(100) DEFAULT NULL,
  `monto` double(16,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_procedimientos`
--

CREATE TABLE `c_procedimientos` (
  `id` int(11) NOT NULL,
  `nombreDeProcedimiento` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_proveedores`
--

CREATE TABLE `c_proveedores` (
  `ID_Proveedor` int(11) NOT NULL,
  `Empresa` varchar(100) DEFAULT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `RUT` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `cve_dane` varchar(50) DEFAULT NULL,
  `ID_Externo` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_proveedor` varchar(20) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `telefono1` varchar(255) DEFAULT NULL,
  `telefono2` varchar(255) DEFAULT NULL,
  `es_cliente` int(11) DEFAULT NULL,
  `longitud` float DEFAULT NULL,
  `latitud` float DEFAULT NULL,
  `es_transportista` int(11) DEFAULT NULL,
  `envio_correo_automatico` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_proyecto`
--

CREATE TABLE `c_proyecto` (
  `Id` int(11) NOT NULL,
  `Cve_Proyecto` varchar(100) NOT NULL,
  `Des_Proyecto` varchar(250) DEFAULT NULL,
  `id_almacen` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_recursos`
--

CREATE TABLE `c_recursos` (
  `id` int(11) NOT NULL,
  `nombreDeRecurso` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_regimenfiscal`
--

CREATE TABLE `c_regimenfiscal` (
  `Id_RegFis` int(11) NOT NULL,
  `Cve_RegFis` varchar(50) NOT NULL,
  `Des_RegFis` varchar(150) NOT NULL,
  `Persona_Fisica` varchar(1) NOT NULL,
  `Persona_Moral` varchar(1) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_serie`
--

CREATE TABLE `c_serie` (
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `Cve_Activo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_servicios`
--

CREATE TABLE `c_servicios` (
  `Id_Servicio` int(11) NOT NULL,
  `Cve_Servicio` varchar(50) NOT NULL,
  `Des_Servicio` varchar(250) DEFAULT NULL,
  `UniMedida` int(11) NOT NULL,
  `Gpo_Servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_sgpoarticulo`
--

CREATE TABLE `c_sgpoarticulo` (
  `id` int(11) NOT NULL,
  `cve_sgpoart` varchar(20) DEFAULT NULL,
  `cve_gpoart` int(11) DEFAULT NULL,
  `des_sgpoart` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `Num_Multiplo` int(11) DEFAULT NULL,
  `Ban_Incluye` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_ssgpoarticulo`
--

CREATE TABLE `c_ssgpoarticulo` (
  `id` int(11) NOT NULL,
  `cve_ssgpoart` varchar(20) DEFAULT NULL,
  `cve_sgpoart` int(11) DEFAULT NULL,
  `des_ssgpoart` varchar(200) DEFAULT NULL,
  `Opcinal` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_sucursal`
--

CREATE TABLE `c_sucursal` (
  `id` int(11) NOT NULL,
  `cve_cia` int(11) NOT NULL,
  `distrito` varchar(255) DEFAULT NULL,
  `des_cia` varchar(100) DEFAULT NULL,
  `des_rfc` varchar(20) DEFAULT NULL,
  `des_direcc` varchar(150) DEFAULT NULL,
  `des_cp` varchar(10) DEFAULT NULL,
  `des_telef` varchar(50) DEFAULT NULL,
  `des_contacto` varchar(100) DEFAULT NULL,
  `des_email` varchar(100) DEFAULT NULL,
  `des_observ` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `clave_sucursal` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipocaja`
--

CREATE TABLE `c_tipocaja` (
  `id_tipocaja` int(11) NOT NULL,
  `clave` varchar(20) DEFAULT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `largo` decimal(10,2) DEFAULT NULL,
  `alto` decimal(10,2) DEFAULT NULL,
  `ancho` decimal(10,2) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Packing` enum('N','S') NOT NULL,
  `peso` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipocia`
--

CREATE TABLE `c_tipocia` (
  `cve_tipcia` int(11) NOT NULL,
  `des_tipcia` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `clave_tcia` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipocliente`
--

CREATE TABLE `c_tipocliente` (
  `id` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `Cve_TipoCte` varchar(20) DEFAULT NULL,
  `Des_TipoCte` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipocliente2`
--

CREATE TABLE `c_tipocliente2` (
  `id` int(11) NOT NULL,
  `id_tipocliente` int(11) DEFAULT NULL,
  `Cve_TipoCte` varchar(20) DEFAULT NULL,
  `Des_TipoCte` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipo_cambio`
--

CREATE TABLE `c_tipo_cambio` (
  `Cve_Moneda_Base` int(11) NOT NULL,
  `Cve_Moneda` int(11) NOT NULL,
  `Fecha` date NOT NULL,
  `Tipo_Cambio` decimal(18,4) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipo_car`
--

CREATE TABLE `c_tipo_car` (
  `Id_Tipo_car` int(11) NOT NULL,
  `TipoCar_Desc` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_tipo_producto`
--

CREATE TABLE `c_tipo_producto` (
  `clave` varchar(50) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_ubicacion`
--

CREATE TABLE `c_ubicacion` (
  `idy_ubica` int(11) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `cve_pasillo` varchar(10) DEFAULT NULL,
  `cve_rack` varchar(10) DEFAULT NULL,
  `cve_nivel` varchar(10) DEFAULT NULL,
  `num_ancho` decimal(10,0) DEFAULT NULL,
  `num_largo` decimal(10,0) DEFAULT NULL,
  `num_alto` decimal(10,0) DEFAULT NULL,
  `num_volumenDisp` decimal(10,0) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `picking` char(1) DEFAULT NULL,
  `Seccion` varchar(10) DEFAULT NULL,
  `Ubicacion` varchar(10) DEFAULT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `PesoMaximo` float DEFAULT NULL,
  `PesoOcupado` float DEFAULT NULL,
  `claverp` varchar(15) DEFAULT NULL,
  `CodigoCSD` varchar(50) DEFAULT NULL,
  `TECNOLOGIA` varchar(6) DEFAULT NULL,
  `Maneja_Cajas` char(1) DEFAULT NULL,
  `Maneja_Piezas` char(1) DEFAULT NULL,
  `Reabasto` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Tipo` varchar(1) DEFAULT NULL,
  `AcomodoMixto` enum('S','N') DEFAULT NULL,
  `AreaProduccion` enum('S','N') DEFAULT NULL,
  `AreaStagging` enum('S','N') DEFAULT NULL,
  `Ptl` enum('S','N') DEFAULT NULL,
  `Maximo` int(11) DEFAULT NULL,
  `Minimo` int(11) DEFAULT NULL,
  `clasif_abc` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_unimed`
--

CREATE TABLE `c_unimed` (
  `id_umed` int(11) NOT NULL,
  `cve_umed` varchar(20) DEFAULT NULL,
  `des_umed` varchar(100) DEFAULT NULL,
  `mav_cveunimed` varchar(20) DEFAULT NULL,
  `imp_cosprom` decimal(19,4) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_usocfdi`
--

CREATE TABLE `c_usocfdi` (
  `Id_CFDI` int(11) NOT NULL,
  `Cve_CFDI` varchar(50) NOT NULL,
  `Des_CFDI` varchar(150) NOT NULL,
  `Persona_Fisica` varchar(1) NOT NULL,
  `Persona_Moral` varchar(1) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_usuario`
--

CREATE TABLE `c_usuario` (
  `id_user` int(11) NOT NULL,
  `cve_usuario` varchar(20) DEFAULT NULL,
  `cve_cia` int(11) DEFAULT NULL,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `perfil` varchar(255) DEFAULT NULL,
  `des_usuario` text NOT NULL,
  `fec_ingreso` datetime DEFAULT NULL,
  `pwd_usuario` varchar(20) DEFAULT NULL,
  `ban_usuario` tinyint(4) NOT NULL,
  `status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `es_cliente` int(11) DEFAULT NULL,
  `cve_almacen` varchar(50) DEFAULT NULL,
  `cve_cliente` varchar(50) DEFAULT NULL,
  `cve_proveedor` varchar(50) DEFAULT NULL,
  `Id_Fcm` varchar(255) DEFAULT NULL,
  `web_apk` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_zonacliente`
--

CREATE TABLE `c_zonacliente` (
  `id` int(11) NOT NULL,
  `Cve_ZonaCte` varchar(20) DEFAULT NULL,
  `Des_ZonaCte` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_zonahoraria`
--

CREATE TABLE `c_zonahoraria` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `id_user` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descarga`
--

CREATE TABLE `descarga` (
  `ID` int(11) NOT NULL,
  `IdRuta` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,2) DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Diao` int(11) DEFAULT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallecob`
--

CREATE TABLE `detallecob` (
  `Id` int(11) NOT NULL,
  `IdCobranza` int(11) DEFAULT NULL,
  `Abono` decimal(10,0) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `SaldoAnt` decimal(10,0) DEFAULT NULL,
  `Saldo` decimal(10,0) DEFAULT NULL,
  `FormaP` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `Documento` varchar(50) DEFAULT NULL,
  `Cliente` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Cancelada` smallint(6) DEFAULT NULL,
  `ClaveBco` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalledevenvases`
--

CREATE TABLE `detalledevenvases` (
  `ID` int(11) NOT NULL,
  `IdEmpresa` varchar(50) NOT NULL,
  `RutaId` int(11) NOT NULL,
  `DiaO` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `CodCli` int(11) NOT NULL,
  `DoctoRef` varchar(50) NOT NULL,
  `FolioDev` varchar(50) NOT NULL,
  `Envase` varchar(50) NOT NULL,
  `SaldoAnt` int(11) DEFAULT NULL,
  `CantDevuelta` int(11) DEFAULT NULL,
  `SaldoActual` int(11) DEFAULT NULL,
  `Tipo` varchar(100) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalledevol`
--

CREATE TABLE `detalledevol` (
  `Id` int(11) NOT NULL,
  `SKU` varchar(50) DEFAULT NULL,
  `Pza` decimal(18,2) DEFAULT NULL,
  `KG` decimal(18,2) DEFAULT NULL,
  `Precio` decimal(10,0) DEFAULT NULL,
  `Importe` decimal(10,0) DEFAULT NULL,
  `EDO` smallint(6) DEFAULT NULL,
  `Motivo` varchar(255) DEFAULT NULL,
  `IVA` decimal(10,0) DEFAULT NULL,
  `IEPS` decimal(10,0) DEFAULT NULL,
  `Devol` int(11) DEFAULT NULL,
  `Docto` int(11) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `Tipo` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `UrlImagen` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallegpopromo`
--

CREATE TABLE `detallegpopromo` (
  `Id` int(11) NOT NULL,
  `PromoId` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `cve_gpoart` varchar(20) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `TipMed` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleld`
--

CREATE TABLE `detalleld` (
  `id` int(11) NOT NULL,
  `ListaId` int(11) DEFAULT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Factor` decimal(18,4) DEFAULT NULL,
  `FactorMax` decimal(18,4) DEFAULT NULL,
  `Minimo` decimal(18,4) DEFAULT NULL,
  `Maximo` decimal(18,4) DEFAULT NULL,
  `Tipo` char(1) DEFAULT NULL,
  `Cve_Almac` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallelp`
--

CREATE TABLE `detallelp` (
  `id` int(11) NOT NULL,
  `ListaId` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `PrecioMin` double(18,3) DEFAULT NULL,
  `PrecioMax` double(18,3) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `ComisionPor` double(18,3) DEFAULT NULL,
  `ComisionMon` double(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallelpromaster`
--

CREATE TABLE `detallelpromaster` (
  `Id` int(11) NOT NULL,
  `IdLm` int(11) DEFAULT NULL,
  `IdPromo` int(11) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepromo`
--

CREATE TABLE `detallepromo` (
  `Id` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `PromoId` int(11) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `TipoProm` varchar(50) DEFAULT NULL,
  `Monto` decimal(18,3) DEFAULT NULL,
  `Volumen` decimal(18,3) DEFAULT NULL,
  `UniMed` int(11) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `Nivel` int(11) DEFAULT NULL,
  `Grupo_Art` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallevet`
--

CREATE TABLE `detallevet` (
  `ID` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Descripcion` varchar(250) DEFAULT NULL,
  `Precio` decimal(10,2) DEFAULT NULL,
  `Pza` decimal(18,2) DEFAULT NULL,
  `Kg` decimal(18,2) DEFAULT NULL,
  `DescPorc` varchar(50) DEFAULT NULL,
  `DescMon` decimal(18,2) DEFAULT NULL,
  `Tipo` int(11) DEFAULT NULL,
  `Docto` varchar(50) DEFAULT NULL,
  `Importe` decimal(10,2) DEFAULT NULL,
  `IVA` decimal(10,2) DEFAULT NULL,
  `IEPS` decimal(10,2) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Comisiones` decimal(10,2) DEFAULT NULL,
  `Utilidad` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `det_planifica_inventario`
--

CREATE TABLE `det_planifica_inventario` (
  `ID_PLAN` int(11) NOT NULL,
  `FECHA_APLICA` datetime NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `status` char(1) DEFAULT NULL,
  `Cve_Usuario` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devenvases`
--

CREATE TABLE `devenvases` (
  `ID` int(11) NOT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `CodCli` int(11) DEFAULT NULL,
  `Docto` varchar(50) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `Devuelto` int(11) DEFAULT NULL,
  `Tipo` varchar(100) DEFAULT NULL,
  `Envase` varchar(50) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `SaldoActual` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `Id` int(11) NOT NULL,
  `CodCliente` int(11) DEFAULT NULL,
  `Devol` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Status` decimal(18,0) DEFAULT NULL,
  `Ruta` int(11) DEFAULT NULL,
  `Vendedor` int(11) DEFAULT NULL,
  `Items` int(11) DEFAULT NULL,
  `KG` decimal(18,2) DEFAULT NULL,
  `IVA` decimal(10,0) DEFAULT NULL,
  `IEPS` decimal(10,0) DEFAULT NULL,
  `Subtotal` decimal(10,0) DEFAULT NULL,
  `Total` decimal(10,0) DEFAULT NULL,
  `EnLetras` varchar(255) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `Docto` int(11) DEFAULT NULL,
  `Cancelada` smallint(6) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diaso`
--

CREATE TABLE `diaso` (
  `Id` int(11) NOT NULL,
  `DiaO` int(11) NOT NULL,
  `Fecha` date DEFAULT NULL,
  `RutaId` int(11) NOT NULL,
  `VProg` decimal(18,0) DEFAULT NULL,
  `Ve` decimal(18,0) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `d_pedido`
--

CREATE TABLE `d_pedido` (
  `Fol_folio` varchar(20) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `Num_cantidad` int(11) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emp_paqueteria`
--

CREATE TABLE `emp_paqueteria` (
  `Id_Empresa` decimal(18,0) DEFAULT NULL,
  `No_Cliente` char(7) DEFAULT NULL,
  `Usuario` varchar(50) DEFAULT NULL,
  `Password` varchar(10) DEFAULT NULL,
  `No_Suscriptor` char(2) DEFAULT NULL,
  `Contacto` varchar(30) DEFAULT NULL,
  `Telefono` varchar(25) DEFAULT NULL,
  `Tel_Celular` varchar(20) DEFAULT NULL,
  `serviceTypeId` int(11) DEFAULT NULL,
  `Id_EstatusGuias` int(11) DEFAULT NULL,
  `Usuario_EstatusGuias` varchar(50) DEFAULT NULL,
  `Pswd_EstatusGuias` varchar(50) DEFAULT NULL,
  `Tiempo_Actualizacion` int(11) DEFAULT NULL,
  `Operador_Logistico` varchar(100) DEFAULT NULL,
  `officeNum` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios_correos`
--

CREATE TABLE `envios_correos` (
  `id` int(11) NOT NULL,
  `tipo_destinatario` enum('proveedor','cliente','compania') NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `almacen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios_reportes`
--

CREATE TABLE `envios_reportes` (
  `id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus_envio`
--

CREATE TABLE `estatus_envio` (
  `id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `estatus` enum('pendiente','enviado','error') NOT NULL,
  `mensaje_error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etl_connections`
--

CREATE TABLE `etl_connections` (
  `id` int(10) UNSIGNED NOT NULL,
  `alias` varchar(64) NOT NULL,
  `friendly_name` varchar(80) NOT NULL,
  `tipo` enum('mysql','mariadb','sqlsrv','pgsql','csv') NOT NULL,
  `host` varchar(120) DEFAULT NULL,
  `puerto` int(11) DEFAULT NULL,
  `db_name` varchar(120) DEFAULT NULL,
  `username` varchar(120) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `opciones_json` longtext DEFAULT NULL CHECK (json_valid(`opciones_json`)),
  `file_path` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etl_object_meta`
--

CREATE TABLE `etl_object_meta` (
  `id` int(11) NOT NULL,
  `alias` varchar(100) NOT NULL,
  `remote_db` varchar(191) NOT NULL,
  `object_name` varchar(191) NOT NULL,
  `comment` text DEFAULT NULL,
  `procesos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_action_at` datetime DEFAULT NULL,
  `last_action_type` varchar(20) DEFAULT NULL,
  `last_action_rows` int(11) DEFAULT NULL,
  `dest_table` varchar(191) DEFAULT NULL,
  `auto_update` tinyint(1) NOT NULL DEFAULT 0,
  `auto_update_cron` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etl_processes`
--

CREATE TABLE `etl_processes` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `group_name` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etl_process_docs`
--

CREATE TABLE `etl_process_docs` (
  `id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL,
  `title` varchar(191) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `mime_type` varchar(191) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etl_process_objects`
--

CREATE TABLE `etl_process_objects` (
  `id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL,
  `alias` varchar(100) NOT NULL,
  `remote_db` varchar(191) NOT NULL,
  `object_name` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `folios`
--

CREATE TABLE `folios` (
  `Fol_Folio` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formaspag`
--

CREATE TABLE `formaspag` (
  `IdFpag` int(11) NOT NULL,
  `Forma` varchar(100) DEFAULT NULL,
  `Clave` varchar(100) DEFAULT NULL,
  `Status` smallint(6) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `interfaz`
--

CREATE TABLE `interfaz` (
  `Ruta` varchar(250) DEFAULT NULL,
  `Intervalo` int(11) DEFAULT NULL,
  `Archivo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventariociclico`
--

CREATE TABLE `inventariociclico` (
  `ID_PLAN` int(11) NOT NULL,
  `FECHA_APLICA` datetime NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `almacen` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lc_diaso`
--

CREATE TABLE `lc_diaso` (
  `empresa_id` int(11) NOT NULL,
  `Id` text DEFAULT NULL,
  `DiaO` text DEFAULT NULL,
  `Fecha` text DEFAULT NULL,
  `RutaId` text DEFAULT NULL,
  `VProg` text DEFAULT NULL,
  `Ve` text DEFAULT NULL,
  `IdEmpresa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lc_folios`
--

CREATE TABLE `lc_folios` (
  `empresa_id` int(11) NOT NULL,
  `Fol_Folio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lc_relclilis`
--

CREATE TABLE `lc_relclilis` (
  `empresa_id` int(11) NOT NULL,
  `Id` text DEFAULT NULL,
  `Id_Destinatario` text DEFAULT NULL,
  `ListaP` text DEFAULT NULL,
  `ListaD` text DEFAULT NULL,
  `ListaPromo` text DEFAULT NULL,
  `DiaVisita` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lc_rel_dest_ruta`
--

CREATE TABLE `lc_rel_dest_ruta` (
  `empresa_id` int(11) NOT NULL,
  `Id` text DEFAULT NULL,
  `Cve_Almac` text DEFAULT NULL,
  `Id_Destinatario` text DEFAULT NULL,
  `Id_Ruta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lc_v_vendedoresruta`
--

CREATE TABLE `lc_v_vendedoresruta` (
  `empresa_id` int(11) NOT NULL,
  `Id_Vendedor` text DEFAULT NULL,
  `Cve_Vendedor` text DEFAULT NULL,
  `Nombre` text DEFAULT NULL,
  `Activo` text DEFAULT NULL,
  `CalleNumero` text DEFAULT NULL,
  `Colonia` text DEFAULT NULL,
  `Ciudad` text DEFAULT NULL,
  `CodigoPostal` text DEFAULT NULL,
  `Estado` text DEFAULT NULL,
  `Pais` text DEFAULT NULL,
  `Id_Fcm` text DEFAULT NULL,
  `Ruta` text DEFAULT NULL,
  `Cve_Ruta` text DEFAULT NULL,
  `IdEmpresa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `listad`
--

CREATE TABLE `listad` (
  `id` int(11) NOT NULL,
  `Lista` varchar(255) DEFAULT NULL,
  `Tipo` char(1) DEFAULT NULL,
  `Caduca` tinyint(1) DEFAULT NULL,
  `FechaIni` date DEFAULT NULL,
  `FechaFin` date DEFAULT NULL,
  `Cve_Almac` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `listap`
--

CREATE TABLE `listap` (
  `id` int(11) NOT NULL,
  `Lista` varchar(255) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `FechaIni` date DEFAULT NULL,
  `FechaFin` date DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `TipoServ` char(1) DEFAULT NULL,
  `id_moneda` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `listapromo`
--

CREATE TABLE `listapromo` (
  `id` int(11) NOT NULL,
  `Lista` varchar(255) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `Caduca` tinyint(1) DEFAULT NULL,
  `FechaI` date DEFAULT NULL,
  `FechaF` date DEFAULT NULL,
  `Grupo` varchar(255) DEFAULT NULL,
  `Activa` tinyint(1) DEFAULT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `listapromomaster`
--

CREATE TABLE `listapromomaster` (
  `Id` int(11) NOT NULL,
  `ListaMaster` varchar(50) DEFAULT NULL,
  `Promociones` varchar(50) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medidores`
--

CREATE TABLE `medidores` (
  `IdRow` int(11) NOT NULL,
  `IdRuta` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `OdometroInicial` decimal(18,3) DEFAULT NULL,
  `OdometroFinal` decimal(18,3) DEFAULT NULL,
  `TanqueInicial` decimal(18,3) DEFAULT NULL,
  `TanqueFinal` decimal(18,3) DEFAULT NULL,
  `LitrosCargados` decimal(18,3) DEFAULT NULL,
  `GastoLitros` decimal(18,3) DEFAULT NULL,
  `Rendimiento` decimal(18,3) DEFAULT NULL,
  `KmR` decimal(18,3) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `IdVehiculo` int(11) DEFAULT NULL,
  `Fol_Ticket` varchar(50) DEFAULT NULL,
  `Proveedor` varchar(255) DEFAULT NULL,
  `Direccion` varchar(255) DEFAULT NULL,
  `Combustible` varchar(255) DEFAULT NULL,
  `Precio` decimal(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `ID` int(11) NOT NULL,
  `Clave` varchar(50) DEFAULT NULL,
  `EnBaseA` varchar(255) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `Mensaje` varchar(255) DEFAULT NULL,
  `FechaInicio` datetime DEFAULT NULL,
  `FechaFinal` datetime DEFAULT NULL,
  `Estado` tinyint(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motivosnoventa`
--

CREATE TABLE `motivosnoventa` (
  `IdMot` int(11) NOT NULL,
  `Motivo` varchar(500) DEFAULT NULL,
  `Clave` varchar(50) DEFAULT NULL,
  `Status` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motivos_devolucion`
--

CREATE TABLE `motivos_devolucion` (
  `MOT_ID` int(11) NOT NULL,
  `MOT_DESC` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Clave_motivo` varchar(20) DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mvtosinvruta`
--

CREATE TABLE `mvtosinvruta` (
  `IdMovStock` int(11) NOT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Id_Ruta` int(11) DEFAULT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Lote` varchar(50) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `Id_TipoMovimiento` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_gral`
--

CREATE TABLE `mv_existencia_gral` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cuarentena` bigint(20) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_gral_produccion_caja`
--

CREATE TABLE `mv_existencia_gral_produccion_caja` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL,
  `Id_Caja` bigint(20) NOT NULL,
  `Cve_Caja` varchar(70) NOT NULL,
  `Cantidad_Caja` decimal(18,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_gral_produccion_caja_temp`
--

CREATE TABLE `mv_existencia_gral_produccion_caja_temp` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL,
  `Id_Caja` bigint(20) NOT NULL,
  `Cve_Caja` varchar(70) NOT NULL,
  `Cantidad_Caja` decimal(18,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_gral_temp`
--

CREATE TABLE `mv_existencia_gral_temp` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cuarentena` bigint(20) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_produccion`
--

CREATE TABLE `mv_existencia_produccion` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(11) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_produccion_temp`
--

CREATE TABLE `mv_existencia_produccion_temp` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(11) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(50,4) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_tmp_produccion`
--

CREATE TABLE `mv_existencia_tmp_produccion` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(11) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(28,4) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_existencia_tmp_produccion_temp`
--

CREATE TABLE `mv_existencia_tmp_produccion_temp` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(11) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(28,4) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_tmp_existencias`
--

CREATE TABLE `mv_tmp_existencias` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(28,4) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mv_tmp_existencias_temp`
--

CREATE TABLE `mv_tmp_existencias_temp` (
  `cve_almac` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(28,4) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `tipo` varchar(9) NOT NULL,
  `Cuarentena` bigint(20) NOT NULL,
  `Cve_Contenedor` varchar(70) NOT NULL,
  `Lote_Alterno` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noventas`
--

CREATE TABLE `noventas` (
  `Id` int(11) NOT NULL,
  `Cliente` int(11) DEFAULT NULL,
  `MotivoId` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `VendedorId` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peddiasig`
--

CREATE TABLE `peddiasig` (
  `ID` int(11) NOT NULL,
  `IdRuta` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(10,0) NOT NULL,
  `Fecha` date DEFAULT NULL,
  `Diao` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Surtidas` decimal(10,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodicidad`
--

CREATE TABLE `periodicidad` (
  `id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `lunes` tinyint(1) DEFAULT NULL,
  `martes` tinyint(1) DEFAULT NULL,
  `miercoles` tinyint(1) DEFAULT NULL,
  `jueves` tinyint(1) DEFAULT NULL,
  `viernes` tinyint(1) DEFAULT NULL,
  `sabado` tinyint(1) DEFAULT NULL,
  `domingo` tinyint(1) DEFAULT NULL,
  `hora_envio` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pregalado`
--

CREATE TABLE `pregalado` (
  `Id` int(11) NOT NULL,
  `SKU` varchar(50) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `Pz` decimal(18,2) DEFAULT NULL,
  `Kg` decimal(18,2) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `Docto` varchar(50) DEFAULT NULL,
  `Cliente` int(11) NOT NULL,
  `Cant` int(11) DEFAULT NULL,
  `Tipmed` varchar(8) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `SKU_Base` varchar(50) DEFAULT NULL,
  `Multiplo_Base` decimal(18,2) DEFAULT NULL,
  `UM_Base` varchar(25) DEFAULT NULL,
  `Multiplo_Regalo` decimal(18,2) DEFAULT NULL,
  `UM_Regalo` varchar(25) DEFAULT NULL,
  `Tipo` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productoenvase`
--

CREATE TABLE `productoenvase` (
  `Id` int(11) NOT NULL,
  `Producto` varchar(50) DEFAULT NULL,
  `Envase` varchar(250) DEFAULT NULL,
  `Cant_Base` int(11) DEFAULT NULL,
  `Cant_Eq` int(11) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recarga`
--

CREATE TABLE `recarga` (
  `ID` int(11) NOT NULL,
  `IdRuta` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,2) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Diao` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Hora` datetime DEFAULT NULL,
  `Surtidas` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relclilis`
--

CREATE TABLE `relclilis` (
  `Id` int(11) NOT NULL,
  `Id_Destinatario` int(11) NOT NULL,
  `ListaP` int(11) DEFAULT NULL,
  `ListaD` int(11) DEFAULT NULL,
  `ListaPromo` int(11) DEFAULT NULL,
  `DiaVisita` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relclirutas`
--

CREATE TABLE `relclirutas` (
  `Id` int(11) NOT NULL,
  `IdCliente` varchar(50) DEFAULT NULL,
  `IdRuta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relcuovend`
--

CREATE TABLE `relcuovend` (
  `Id` int(11) NOT NULL,
  `CuoId` int(11) DEFAULT NULL,
  `VendeId` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reldaycli`
--

CREATE TABLE `reldaycli` (
  `Id` int(11) NOT NULL,
  `Cve_Ruta` varchar(50) DEFAULT NULL,
  `Cve_Cliente` varchar(50) DEFAULT NULL,
  `Id_Destinatario` int(11) NOT NULL,
  `Cve_Vendedor` varchar(50) DEFAULT NULL,
  `Lu` int(11) DEFAULT NULL,
  `Ma` int(11) DEFAULT NULL,
  `Mi` int(11) DEFAULT NULL,
  `Ju` int(11) DEFAULT NULL,
  `Vi` int(11) DEFAULT NULL,
  `Sa` int(11) DEFAULT NULL,
  `Do` int(11) DEFAULT NULL,
  `Cve_Almac` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relmens`
--

CREATE TABLE `relmens` (
  `IDRow` int(11) NOT NULL,
  `MenId` int(11) DEFAULT NULL,
  `CodCliente` varchar(255) DEFAULT NULL,
  `IdCliente` int(11) DEFAULT NULL,
  `CodProducto` varchar(255) DEFAULT NULL,
  `IdProducto` int(11) DEFAULT NULL,
  `CodRuta` int(11) DEFAULT NULL,
  `IdRuta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reloperaciones`
--

CREATE TABLE `reloperaciones` (
  `IdK` int(11) NOT NULL,
  `Id` decimal(18,0) NOT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `CodCli` int(11) DEFAULT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Total` decimal(18,3) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relvendrutas`
--

CREATE TABLE `relvendrutas` (
  `Id` int(11) NOT NULL,
  `IdVendedor` int(11) DEFAULT NULL,
  `IdRuta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_almacen_sap`
--

CREATE TABLE `rel_almacen_sap` (
  `Id` int(11) NOT NULL,
  `Cve_Almac` varchar(50) NOT NULL,
  `Cve_SAP` varchar(50) NOT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Alm_Traslado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_articulo_almacen`
--

CREATE TABLE `rel_articulo_almacen` (
  `Id` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Grupo_ID` int(11) DEFAULT NULL,
  `Clasificacion_ID` int(11) DEFAULT NULL,
  `Tipo_Art_ID` int(11) DEFAULT NULL,
  `StockMax` decimal(18,10) DEFAULT NULL,
  `StockMin` decimal(18,10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_articulo_proveedor`
--

CREATE TABLE `rel_articulo_proveedor` (
  `id` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_articulo_proveedor_respaldo_sc`
--

CREATE TABLE `rel_articulo_proveedor_respaldo_sc` (
  `id` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_art_carac`
--

CREATE TABLE `rel_art_carac` (
  `Cve_Articulo` varchar(50) NOT NULL,
  `Id_Carac` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_cliente_almacen`
--

CREATE TABLE `rel_cliente_almacen` (
  `Id` int(11) NOT NULL,
  `Cve_Clte` varchar(50) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_dest_ruta`
--

CREATE TABLE `rel_dest_ruta` (
  `Id` int(11) NOT NULL,
  `Cve_Almac` varchar(50) DEFAULT NULL,
  `Id_Destinatario` int(11) NOT NULL,
  `Id_Ruta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_ds_plan`
--

CREATE TABLE `rel_ds_plan` (
  `id` int(11) NOT NULL,
  `ID_PLAN` int(11) DEFAULT NULL,
  `ID_DIA` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_modulotipo`
--

CREATE TABLE `rel_modulotipo` (
  `ID_Permiso` int(11) NOT NULL,
  `Id_Tipo` int(11) NOT NULL,
  `Cve_Almac` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_ordenembarque_foto`
--

CREATE TABLE `rel_ordenembarque_foto` (
  `id` int(11) NOT NULL,
  `id_ordenembarque` int(11) NOT NULL,
  `nombre_foto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_pedidodest`
--

CREATE TABLE `rel_pedidodest` (
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `Id_Destinatario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_pedido_factura`
--

CREATE TABLE `rel_pedido_factura` (
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Factura` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_regfis_cfdi`
--

CREATE TABLE `rel_regfis_cfdi` (
  `Id_RegFis` int(11) NOT NULL,
  `Id_CFDI` int(11) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_rutasentregas`
--

CREATE TABLE `rel_rutasentregas` (
  `id_ruta_entrega` int(11) NOT NULL,
  `id_ruta_venta_preventa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_ruta_agentes`
--

CREATE TABLE `rel_ruta_agentes` (
  `id` int(11) NOT NULL,
  `cve_ruta` varchar(50) DEFAULT NULL,
  `cve_vendedor` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_ruta_transporte`
--

CREATE TABLE `rel_ruta_transporte` (
  `id` int(11) NOT NULL,
  `cve_ruta` varchar(50) DEFAULT NULL,
  `id_transporte` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_tarima_caja`
--

CREATE TABLE `rel_tarima_caja` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Sufijo` int(11) NOT NULL,
  `nTarima` int(11) NOT NULL,
  `Cve_CajaMix` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_tipocaja_art`
--

CREATE TABLE `rel_tipocaja_art` (
  `Id` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Id_TipoCaja` int(11) DEFAULT NULL,
  `Num_Multiplo` int(11) DEFAULT NULL,
  `CB_Caja` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_uembarquepedido`
--

CREATE TABLE `rel_uembarquepedido` (
  `id` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `Sufijo` int(11) DEFAULT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_uembarque_ruta`
--

CREATE TABLE `rel_uembarque_ruta` (
  `id` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `cve_ruta` varchar(20) DEFAULT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_usuario_ruta`
--

CREATE TABLE `rel_usuario_ruta` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_ruta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `clientes` tinyint(1) DEFAULT NULL,
  `proveedores` tinyint(1) DEFAULT NULL,
  `companias` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudservicio`
--

CREATE TABLE `solicitudservicio` (
  `id` int(11) NOT NULL,
  `FolServicio` varchar(50) DEFAULT NULL,
  `CodCliente` varchar(50) DEFAULT NULL,
  `DescripcionServicio` varchar(255) DEFAULT NULL,
  `Prioridad` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `FechaServicio` datetime DEFAULT NULL,
  `Cancelado` bit(1) DEFAULT NULL,
  `Observaciones` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_codesop`
--

CREATE TABLE `stg_codesop` (
  `Codi` varchar(50) NOT NULL,
  `Operacion` varchar(255) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `EsRecarga` tinyint(1) DEFAULT NULL,
  `Gasto` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_colaprocesamiento`
--

CREATE TABLE `stg_colaprocesamiento` (
  `id` int(11) NOT NULL,
  `idVendedor` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `idEmpresa` varchar(50) DEFAULT NULL,
  `nombreArchivo` varchar(500) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Procesado` varchar(50) DEFAULT NULL,
  `FechaProcesado` datetime DEFAULT NULL,
  `idRuta` int(11) DEFAULT NULL,
  `rutaOpcional` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_configrutasp`
--

CREATE TABLE `stg_configrutasp` (
  `Id` int(11) NOT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `ModelPrinter` varchar(50) DEFAULT NULL,
  `VelCom` varchar(50) DEFAULT NULL,
  `COM` varchar(50) DEFAULT NULL,
  `Server` varchar(255) DEFAULT NULL,
  `Puerto` int(11) DEFAULT NULL,
  `ServerGPS` varchar(50) DEFAULT NULL,
  `GPS` smallint(6) DEFAULT NULL,
  `PuertoG` varchar(50) DEFAULT NULL,
  `PagoContado` smallint(6) DEFAULT NULL,
  `CteNvo` smallint(6) DEFAULT NULL,
  `CveCteNvo` smallint(6) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `SugerirCant` smallint(6) DEFAULT NULL,
  `PromoEq` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_continuidad`
--

CREATE TABLE `stg_continuidad` (
  `RutaID` int(11) NOT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `FolVta` int(11) DEFAULT NULL,
  `FolPed` int(11) DEFAULT NULL,
  `FolDevol` int(11) DEFAULT NULL,
  `FolCob` int(11) DEFAULT NULL,
  `UDiaO` int(11) DEFAULT NULL,
  `CteNvo` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `FolGto` int(11) DEFAULT NULL,
  `FolServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_cuotas`
--

CREATE TABLE `stg_cuotas` (
  `Id` int(11) NOT NULL,
  `Clave` varchar(50) DEFAULT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `UniMed` varchar(50) DEFAULT NULL,
  `Cantidad` varchar(50) DEFAULT NULL,
  `FechaI` datetime DEFAULT NULL,
  `FechaF` datetime DEFAULT NULL,
  `Producto` varchar(50) DEFAULT NULL,
  `Tipo` tinyint(1) DEFAULT NULL,
  `Activa` tinyint(1) DEFAULT NULL,
  `NivelNum` tinyint(1) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_c_aduanas`
--

CREATE TABLE `stg_c_aduanas` (
  `Id_CatAduana` int(11) NOT NULL,
  `Cve_CatAduana` varchar(50) DEFAULT NULL,
  `Des_CatAduana` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_c_charolas`
--

CREATE TABLE `stg_c_charolas` (
  `empresa_id` int(11) NOT NULL,
  `IDContenedor` text DEFAULT NULL,
  `cve_almac` text DEFAULT NULL,
  `Clave_Contenedor` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `Permanente` text DEFAULT NULL,
  `Pedido` text DEFAULT NULL,
  `sufijo` text DEFAULT NULL,
  `tipo` text DEFAULT NULL,
  `Activo` text DEFAULT NULL,
  `alto` text DEFAULT NULL,
  `ancho` text DEFAULT NULL,
  `fondo` text DEFAULT NULL,
  `peso` text DEFAULT NULL,
  `pesomax` text DEFAULT NULL,
  `capavol` text DEFAULT NULL,
  `Costo` text DEFAULT NULL,
  `CveLP` text DEFAULT NULL,
  `TipoGen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_descarga`
--

CREATE TABLE `stg_descarga` (
  `ID` int(11) NOT NULL,
  `IdRuta` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,2) DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Diao` int(11) DEFAULT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_detallecob`
--

CREATE TABLE `stg_detallecob` (
  `Id` int(11) NOT NULL,
  `IdCobranza` int(11) DEFAULT NULL,
  `Abono` decimal(10,0) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `SaldoAnt` decimal(10,0) DEFAULT NULL,
  `Saldo` decimal(10,0) DEFAULT NULL,
  `FormaP` int(11) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `Documento` varchar(50) DEFAULT NULL,
  `Cliente` varchar(50) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `Cancelada` smallint(6) DEFAULT NULL,
  `ClaveBco` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg_detalledevenvases`
--

CREATE TABLE `stg_detalledevenvases` (
  `ID` int(11) NOT NULL,
  `IdEmpresa` varchar(50) NOT NULL,
  `RutaId` int(11) NOT NULL,
  `DiaO` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `CodCli` int(11) NOT NULL,
  `DoctoRef` varchar(50) NOT NULL,
  `FolioDev` varchar(50) NOT NULL,
  `Envase` varchar(50) NOT NULL,
  `SaldoAnt` int(11) DEFAULT NULL,
  `CantDevuelta` int(11) DEFAULT NULL,
  `SaldoActual` int(11) DEFAULT NULL,
  `Tipo` varchar(100) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg__th_entalmacen`
--

CREATE TABLE `stg__th_entalmacen` (
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `Fec_Entrada` datetime DEFAULT NULL,
  `fol_oep` varchar(20) DEFAULT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Cve_Proveedor` int(11) DEFAULT NULL,
  `STATUS` char(1) DEFAULT NULL,
  `Cve_Autorizado` char(10) DEFAULT NULL,
  `TieneOE` char(1) DEFAULT NULL,
  `statusaurora` int(11) DEFAULT NULL,
  `id_ocompra` int(11) DEFAULT NULL,
  `placas` varchar(20) DEFAULT NULL,
  `entarimado` char(1) DEFAULT NULL,
  `bufer` varchar(20) DEFAULT NULL,
  `HoraInicio` datetime DEFAULT NULL,
  `ID_Protocolo` varchar(10) DEFAULT NULL,
  `Consec_protocolo` int(11) DEFAULT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stg__ts_ubicxart_old`
--

CREATE TABLE `stg__ts_ubicxart_old` (
  `cve_articulo` varchar(20) DEFAULT NULL,
  `idy_ubica` int(11) NOT NULL,
  `CapacidadMinima` int(11) DEFAULT NULL,
  `CapacidadMaxima` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock`
--

CREATE TABLE `stock` (
  `IdStock` int(11) NOT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Stock` decimal(18,2) DEFAULT NULL,
  `Ruta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stockhistorico`
--

CREATE TABLE `stockhistorico` (
  `Articulo` varchar(50) DEFAULT NULL,
  `Stock` decimal(18,2) DEFAULT NULL,
  `RutaID` int(11) DEFAULT NULL,
  `Fecha` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `DiaO` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stockrutas`
--

CREATE TABLE `stockrutas` (
  `IdStock` int(11) DEFAULT NULL,
  `Articulo` varchar(55) DEFAULT NULL,
  `Stock` decimal(40,4) DEFAULT NULL,
  `Ruta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_rutas`
--

CREATE TABLE `stock_rutas` (
  `IdStock` int(11) DEFAULT NULL,
  `Articulo` varchar(55) DEFAULT NULL,
  `Stock` double DEFAULT NULL,
  `Ruta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `s_etiquetas`
--

CREATE TABLE `s_etiquetas` (
  `MODULO` char(12) NOT NULL,
  `NOMBRE` char(20) NOT NULL,
  `CADENA` text DEFAULT NULL,
  `TIPO_IMPRESORA` char(10) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Num_Regs` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `s_impresoras`
--

CREATE TABLE `s_impresoras` (
  `cve_almac` int(11) NOT NULL,
  `IP` varchar(50) NOT NULL,
  `TIPO_IMPRESORA` char(10) DEFAULT NULL,
  `NOMBRE` char(20) DEFAULT NULL,
  `Marca` varchar(100) DEFAULT NULL,
  `Modelo` varchar(100) DEFAULT NULL,
  `Densidad_Imp` int(11) DEFAULT NULL,
  `TIPO_CONEXION` varchar(2) DEFAULT NULL,
  `PUERTO` int(11) NOT NULL,
  `TiempoEspera` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `s_modulos`
--

CREATE TABLE `s_modulos` (
  `MODULO` varchar(50) NOT NULL,
  `DESCRIPCION` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `s_permisos_modulo`
--

CREATE TABLE `s_permisos_modulo` (
  `MODULO` varchar(50) DEFAULT NULL,
  `DESCRIPCION` varchar(50) DEFAULT NULL,
  `STATUS` char(1) DEFAULT NULL,
  `ID_PERMISO` int(11) NOT NULL,
  `MSGERROR` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `s_tipoimpresoras`
--

CREATE TABLE `s_tipoimpresoras` (
  `id` int(11) NOT NULL,
  `Cve_TipoImp` varchar(50) DEFAULT NULL,
  `Des_TipoImp` varchar(250) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_acuserecivo`
--

CREATE TABLE `td_acuserecivo` (
  `ID_Acuse` int(11) NOT NULL,
  `Fol_folio` varchar(20) NOT NULL,
  `orden` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_aduana`
--

CREATE TABLE `td_aduana` (
  `Id_DetAduana` int(11) NOT NULL,
  `ID_Aduana` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cantidad` double DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `caducidad` datetime DEFAULT NULL,
  `temperatura` varchar(20) DEFAULT NULL,
  `num_orden` int(11) DEFAULT NULL,
  `Ingresado` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `costo` float(100,2) DEFAULT NULL,
  `IVA` decimal(10,0) DEFAULT NULL,
  `Item` varchar(20) DEFAULT NULL,
  `Id_UniMed` int(11) DEFAULT NULL,
  `Fec_Entrega` date DEFAULT NULL,
  `Ref_Docto` varchar(120) DEFAULT NULL,
  `Peso` decimal(18,3) DEFAULT NULL,
  `MarcaNumTotBultos` varchar(70) DEFAULT NULL,
  `Factura` varchar(120) DEFAULT NULL,
  `Fec_Factura` date DEFAULT NULL,
  `Contenedores` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_aduanacaja`
--

CREATE TABLE `td_aduanacaja` (
  `Num_Orden` int(11) NOT NULL,
  `Cve_Almac` varchar(50) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `PzsXCaja` decimal(18,4) DEFAULT NULL,
  `Id_Caja` int(11) DEFAULT NULL,
  `ClaveEtiqueta` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_aduanaxtarima`
--

CREATE TABLE `td_aduanaxtarima` (
  `Id` int(11) NOT NULL,
  `Num_Orden` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `Recibida` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_aduana_extra`
--

CREATE TABLE `td_aduana_extra` (
  `num_orden` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `ParteM3` varchar(120) DEFAULT NULL,
  `Parte` varchar(120) DEFAULT NULL,
  `Fraccion` int(11) NOT NULL,
  `Nico` int(11) DEFAULT NULL,
  `Des_AA` varchar(200) DEFAULT NULL,
  `CantidadFactura` decimal(18,3) DEFAULT NULL,
  `UMComercializacion` int(11) DEFAULT NULL,
  `CantidadTarifa` decimal(18,3) DEFAULT NULL,
  `UMTarifa` int(11) DEFAULT NULL,
  `PrecioUnitario` decimal(18,3) DEFAULT NULL,
  `ValorFactura` decimal(18,3) DEFAULT NULL,
  `UMCOVE` varchar(70) DEFAULT NULL,
  `CantidadCOVE` decimal(18,3) DEFAULT NULL,
  `ValorMercanciaCOVE` decimal(18,3) DEFAULT NULL,
  `PrecioUnitarioCOVE` decimal(18,4) DEFAULT NULL,
  `DescripcionFacturaCOVE` varchar(200) DEFAULT NULL,
  `PlacasTranposte` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_ajusteexist`
--

CREATE TABLE `td_ajusteexist` (
  `fol_folio` varchar(50) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `num_cantant` double DEFAULT NULL,
  `num_cantnva` double DEFAULT NULL,
  `imp_cosprom` decimal(19,4) DEFAULT NULL,
  `Id_Motivo` int(11) DEFAULT NULL,
  `Tipo_Cat` char(1) DEFAULT NULL,
  `ntarima` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_apartado`
--

CREATE TABLE `td_apartado` (
  `ID_Apartado` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_backorder`
--

CREATE TABLE `td_backorder` (
  `Folio_BackO` varchar(20) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Cantidad_Pedido` double NOT NULL,
  `Cantidad_BO` double NOT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_cajamixta`
--

CREATE TABLE `td_cajamixta` (
  `Cve_CajaMixD` int(11) NOT NULL,
  `Cve_CajaMix` int(11) NOT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Num_Empacados` float DEFAULT NULL,
  `Ban_Embarcado` enum('S','N') DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_consolidado`
--

CREATE TABLE `td_consolidado` (
  `id` int(11) NOT NULL,
  `Fol_PedidoCon` varchar(20) DEFAULT NULL,
  `No_OrdComp` varchar(15) DEFAULT NULL,
  `Fec_OrdCom` datetime NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(100) DEFAULT NULL,
  `Cant_Pedida` double NOT NULL,
  `Unid_Empaque` varchar(6) DEFAULT NULL,
  `Tot_Cajas` int(11) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Fact_Madre` varchar(35) DEFAULT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `Fol_Folio` varchar(200) DEFAULT NULL,
  `CodB_Cte` varchar(20) DEFAULT NULL,
  `Cod_PV` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacen`
--

CREATE TABLE `td_entalmacen` (
  `empresa_id` int(11) NOT NULL,
  `fol_folio` text DEFAULT NULL,
  `cve_articulo` text DEFAULT NULL,
  `cve_lote` text DEFAULT NULL,
  `CantidadPedida` text DEFAULT NULL,
  `CantidadRecibida` text DEFAULT NULL,
  `CantidadDisponible` text DEFAULT NULL,
  `CantidadUbicada` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `numero_serie` text DEFAULT NULL,
  `id` text DEFAULT NULL,
  `cve_usuario` text DEFAULT NULL,
  `cve_ubicacion` text DEFAULT NULL,
  `fecha_inicio` text DEFAULT NULL,
  `fecha_fin` text DEFAULT NULL,
  `tipo_entrada` text DEFAULT NULL,
  `costoUnitario` text DEFAULT NULL,
  `num_orden` text DEFAULT NULL,
  `IVA` text DEFAULT NULL,
  `num_pedimento` text DEFAULT NULL,
  `fecha_pedimento` text DEFAULT NULL,
  `factura_articulo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacencaja`
--

CREATE TABLE `td_entalmacencaja` (
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Almac` varchar(50) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `PzsXCaja` decimal(18,4) DEFAULT NULL,
  `Id_Caja` int(11) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) DEFAULT NULL,
  `Ubicada` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacencarac`
--

CREATE TABLE `td_entalmacencarac` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Id_Carac` int(11) DEFAULT NULL,
  `Cant_Sol` decimal(18,4) DEFAULT NULL,
  `Cant_Rec` decimal(18,4) DEFAULT NULL,
  `Cant_Ubic` decimal(18,4) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) DEFAULT NULL,
  `Ubicada` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacenxtarima`
--

CREATE TABLE `td_entalmacenxtarima` (
  `fol_folio` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `ClaveEtiqueta` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `Ubicada` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `PzsXCaja` int(11) NOT NULL,
  `Abierto` tinyint(1) NOT NULL,
  `Observacion` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacen_enviasap`
--

CREATE TABLE `td_entalmacen_enviasap` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_lote` varchar(50) DEFAULT NULL,
  `Cant_Rec` decimal(18,3) DEFAULT NULL,
  `Item` varchar(20) DEFAULT NULL,
  `Fec_Envio` datetime DEFAULT NULL,
  `Enviado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacen_fotos`
--

CREATE TABLE `td_entalmacen_fotos` (
  `id` int(11) NOT NULL,
  `td_entalmacen_producto_id` int(11) NOT NULL,
  `ruta` text NOT NULL,
  `descripcion` varchar(40) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `foto` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_entalmacen_peso`
--

CREATE TABLE `td_entalmacen_peso` (
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Peso_Bruto` decimal(18,4) DEFAULT NULL,
  `Peso_Tar` decimal(18,4) DEFAULT NULL,
  `Peso_Neto` decimal(18,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_factura`
--

CREATE TABLE `td_factura` (
  `Id_Fac` int(11) NOT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Item` int(11) NOT NULL,
  `CPROWNUM` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,4) NOT NULL,
  `Cantidad_Surtida` decimal(18,4) NOT NULL,
  `Fecha_Modif` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_incidencia`
--

CREATE TABLE `td_incidencia` (
  `ID_Incidencia` int(11) NOT NULL,
  `ID_Detalle` int(11) NOT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `Caducidad` datetime DEFAULT NULL,
  `Observaciones` varchar(200) DEFAULT NULL,
  `Fol_folio` varchar(20) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `clave` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_movsauxiliar`
--

CREATE TABLE `td_movsauxiliar` (
  `cve_cia` int(11) NOT NULL,
  `cve_annio` int(11) NOT NULL,
  `cve_mes` int(11) NOT NULL,
  `cve_tipmov` varchar(2) NOT NULL,
  `fec_movto` datetime NOT NULL,
  `fol_folmov` varchar(10) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Id_Reg` decimal(18,0) NOT NULL,
  `num_cant` double DEFAULT NULL,
  `num_signo` smallint(6) DEFAULT NULL,
  `imp_importe` decimal(19,4) DEFAULT NULL,
  `imp_cosprom` decimal(19,4) DEFAULT NULL,
  `num_existf` double DEFAULT NULL,
  `imp_importef` decimal(19,4) DEFAULT NULL,
  `cve_ucosto` int(11) DEFAULT NULL,
  `num_ordprod` varchar(10) DEFAULT NULL,
  `des_otros` varchar(30) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_ordenembarque`
--

CREATE TABLE `td_ordenembarque` (
  `ID_OEmbarque` int(11) NOT NULL,
  `Fol_folio` varchar(20) NOT NULL,
  `status` char(1) DEFAULT NULL,
  `orden_stop` int(11) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_ordenprod`
--

CREATE TABLE `td_ordenprod` (
  `id_ord` bigint(20) NOT NULL,
  `Folio_Pro` varchar(50) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Fecha_Prod` datetime DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `Usr_Armo` char(10) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `id_art_rel` int(11) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cve_Almac_Ori` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_pedido`
--

CREATE TABLE `td_pedido` (
  `id` int(11) NOT NULL,
  `Fol_folio` varchar(20) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `Num_cantidad` double DEFAULT NULL,
  `id_unimed` int(11) DEFAULT NULL,
  `Num_Meses` int(11) DEFAULT NULL,
  `SurtidoXCajas` int(11) DEFAULT NULL,
  `SurtidoXPiezas` int(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Cve_Cot` varchar(50) DEFAULT NULL,
  `factor` double DEFAULT NULL,
  `itemPos` int(11) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `Num_revisadas` double UNSIGNED NOT NULL,
  `Num_Empacados` float DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Auditado` varchar(1) DEFAULT NULL,
  `Precio_unitario` double(18,3) DEFAULT NULL,
  `Desc_Importe` double(18,3) DEFAULT NULL,
  `IVA` double(18,3) DEFAULT NULL,
  `id_ot` int(11) DEFAULT NULL,
  `Cve_Almac_Ori` varchar(50) DEFAULT NULL,
  `Fec_Entrega` date DEFAULT NULL,
  `Valor_Expo` decimal(18,3) DEFAULT NULL,
  `Valor_Comercial_MN` decimal(18,3) DEFAULT NULL,
  `Valor_Aduana` decimal(18,3) DEFAULT NULL,
  `Valor_Comercial_DLL` decimal(18,3) DEFAULT NULL,
  `Proyecto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_pedidoxtarima`
--

CREATE TABLE `td_pedidoxtarima` (
  `id` int(11) NOT NULL,
  `Fol_folio` varchar(20) DEFAULT NULL,
  `nTarima` int(11) DEFAULT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `Num_cantidad` decimal(18,3) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_pedido_ptl`
--

CREATE TABLE `td_pedido_ptl` (
  `Almacen` int(11) NOT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` int(11) NOT NULL,
  `Estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_pedservicios`
--

CREATE TABLE `td_pedservicios` (
  `id` int(11) NOT NULL,
  `Fol_Folio` varchar(50) NOT NULL,
  `Cve_Almac` varchar(50) DEFAULT NULL,
  `Cve_Servicio` varchar(50) NOT NULL,
  `Num_cantidad` decimal(18,4) DEFAULT NULL,
  `id_unimed` int(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `itemPos` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Precio_unitario` double(18,3) DEFAULT NULL,
  `Desc_Importe` double(18,3) DEFAULT NULL,
  `IVA` double(18,3) DEFAULT NULL,
  `Id_Moneda` int(11) NOT NULL,
  `Referencia` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_recorrido_reavastecimiento`
--

CREATE TABLE `td_recorrido_reavastecimiento` (
  `Id` int(11) NOT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Secuencia` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(250) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Fec_Caducidad` datetime DEFAULT NULL,
  `Tomadas` decimal(18,3) DEFAULT NULL,
  `Colocadas` decimal(18,3) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_reladuanaoc`
--

CREATE TABLE `td_reladuanaoc` (
  `Id` int(11) NOT NULL,
  `Num_Orden` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `ClaveEtiqueta` varchar(70) DEFAULT NULL,
  `Cantidad` float NOT NULL,
  `ReferenciaOC` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_ruta_acomodo`
--

CREATE TABLE `td_ruta_acomodo` (
  `id_zona` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_ruta_surtido`
--

CREATE TABLE `td_ruta_surtido` (
  `idr` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_salalmacen`
--

CREATE TABLE `td_salalmacen` (
  `fol_folio` varchar(50) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `num_cantsurt` double DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_subpedido`
--

CREATE TABLE `td_subpedido` (
  `fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Sufijo` int(11) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `Num_Cantidad` decimal(18,4) NOT NULL,
  `Nun_Surtida` double DEFAULT NULL,
  `ManejaCajas` char(1) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Num_Revisda` int(11) DEFAULT NULL,
  `Num_Meses` int(11) DEFAULT NULL,
  `Autorizado` char(1) DEFAULT NULL,
  `ManejaPiezas` char(1) DEFAULT NULL,
  `Cve_Lote` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_surtidocajas`
--

CREATE TABLE `td_surtidocajas` (
  `fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Sufijo` int(11) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `LOTE` varchar(50) NOT NULL,
  `PiezasXCaja` decimal(18,4) NOT NULL,
  `Id_Caja` int(11) NOT NULL,
  `nTarima` int(11) NOT NULL,
  `Revisadas` decimal(18,4) NOT NULL,
  `status` char(1) DEFAULT NULL,
  `Empacado` char(1) DEFAULT NULL,
  `Embarcado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_surtidopiezas`
--

CREATE TABLE `td_surtidopiezas` (
  `fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Sufijo` int(11) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `LOTE` varchar(50) NOT NULL,
  `Cantidad` decimal(18,4) NOT NULL,
  `revisadas` double DEFAULT NULL,
  `Num_Empacados` double DEFAULT NULL,
  `status` char(10) DEFAULT NULL,
  `empacado` char(10) DEFAULT NULL,
  `embarcado` char(1) DEFAULT NULL,
  `Id_Proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `td_tarimatomada`
--

CREATE TABLE `td_tarimatomada` (
  `fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Sufijo` int(11) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `Folio_Tarima` int(11) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `tomadas` int(11) DEFAULT NULL,
  `ubicacion` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_acuserecivo`
--

CREATE TABLE `th_acuserecivo` (
  `ID_Acuse` int(11) NOT NULL,
  `FechaEntrega` datetime DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_aduana`
--

CREATE TABLE `th_aduana` (
  `ID_Aduana` int(11) NOT NULL,
  `num_pedimento` int(11) DEFAULT NULL,
  `fech_pedimento` datetime DEFAULT NULL,
  `aduana` varchar(25) DEFAULT NULL,
  `Factura` varchar(50) DEFAULT NULL,
  `fech_llegPed` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `ID_Protocolo` varchar(10) DEFAULT NULL,
  `Consec_protocolo` int(11) DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `Cve_Almac` varchar(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `recurso` varchar(50) DEFAULT NULL,
  `procedimiento` varchar(120) DEFAULT NULL,
  `AduanaDespacho` int(11) DEFAULT NULL,
  `dictamen` varchar(50) DEFAULT NULL,
  `presupuesto` varchar(50) DEFAULT NULL,
  `condicionesDePago` varchar(50) DEFAULT NULL,
  `lugarDeEntrega` varchar(200) DEFAULT NULL,
  `fechaDeFallo` datetime DEFAULT NULL,
  `plazoDeEntrega` varchar(30) DEFAULT NULL,
  `Proyecto` varchar(100) DEFAULT NULL,
  `areaSolicitante` varchar(50) DEFAULT NULL,
  `numSuficiencia` varchar(30) DEFAULT NULL,
  `fechaSuficiencia` datetime DEFAULT NULL,
  `fechaContrato` datetime DEFAULT NULL,
  `montoSuficiencia` double(10,2) DEFAULT NULL,
  `numeroContrato` varchar(30) DEFAULT NULL,
  `importeAlmacenado` double(10,2) DEFAULT NULL,
  `Pedimento` varchar(100) DEFAULT NULL,
  `BlMaster` varchar(120) DEFAULT NULL,
  `BlHouse` varchar(120) DEFAULT NULL,
  `Tipo_Cambio` decimal(18,5) DEFAULT NULL,
  `Id_moneda` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_aduana_extra`
--

CREATE TABLE `th_aduana_extra` (
  `num_pedimento` int(11) NOT NULL,
  `ClaveDestinatario` varchar(50) DEFAULT NULL,
  `PaisOrigenDestino` varchar(50) DEFAULT NULL,
  `PaisVendedorComprador` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_ajusteexist`
--

CREATE TABLE `th_ajusteexist` (
  `fol_folio` varchar(50) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `fec_ajuste` datetime DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `des_observ` varchar(150) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_apartado`
--

CREATE TABLE `th_apartado` (
  `ID_Apartado` int(11) NOT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `comentarios` varchar(200) DEFAULT NULL,
  `fechaApartado` datetime DEFAULT NULL,
  `fechaLiveracion` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_backorder`
--

CREATE TABLE `th_backorder` (
  `Folio_BackO` varchar(20) NOT NULL,
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `Fec_Pedido` datetime DEFAULT NULL,
  `Fec_Entrega` datetime DEFAULT NULL,
  `Fec_BO` datetime DEFAULT NULL,
  `Pick_num` varchar(20) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_cajaetiqueta`
--

CREATE TABLE `th_cajaetiqueta` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` varchar(50) NOT NULL,
  `Fecha` datetime NOT NULL,
  `Etiqueta` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_cajamixta`
--

CREATE TABLE `th_cajamixta` (
  `Cve_CajaMix` int(11) NOT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `Sufijo` int(11) DEFAULT NULL,
  `NCaja` int(11) DEFAULT NULL,
  `abierta` char(1) DEFAULT NULL,
  `embarcada` char(1) DEFAULT NULL,
  `TipoCaja` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_tipocaja` int(11) DEFAULT NULL,
  `Guia` varchar(50) DEFAULT NULL,
  `Peso` decimal(10,2) DEFAULT NULL,
  `Subida` char(1) DEFAULT NULL,
  `Status_Guia` varchar(500) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `etiqueta` enum('N','S') DEFAULT NULL,
  `CB_Guia` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_consolidado`
--

CREATE TABLE `th_consolidado` (
  `id_consolidado` int(11) NOT NULL,
  `CodB_Prov` varchar(35) DEFAULT NULL,
  `NIT_Prov` varchar(35) DEFAULT NULL,
  `Nom_Prov` varchar(70) DEFAULT NULL,
  `Cve_CteCon` varchar(35) DEFAULT NULL,
  `CodB_CteCon` varchar(35) DEFAULT NULL,
  `Nom_CteCon` varchar(70) DEFAULT NULL,
  `Dir_CteCon` varchar(50) DEFAULT NULL,
  `Cd_CteCon` varchar(50) DEFAULT NULL,
  `NIT_CteCon` varchar(35) DEFAULT NULL,
  `Cod_CteCon` varchar(35) DEFAULT NULL,
  `CodB_CteEnv` varchar(35) DEFAULT NULL,
  `Nom_CteEnv` varchar(70) DEFAULT NULL,
  `Dir_CteEnv` varchar(50) DEFAULT NULL,
  `Cd_CteEnv` varchar(50) DEFAULT NULL,
  `Tel_CteEnv` varchar(30) DEFAULT NULL,
  `Fec_Entrega` datetime NOT NULL,
  `Tot_Cajas` int(11) DEFAULT NULL,
  `Tot_Pzs` int(11) DEFAULT NULL,
  `Placa_Trans` varchar(15) DEFAULT NULL,
  `Sellos` varchar(100) DEFAULT NULL,
  `Fol_PedidoCon` varchar(20) NOT NULL,
  `No_OrdComp` varchar(15) NOT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Cve_Usuario` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_dest_pedido`
--

CREATE TABLE `th_dest_pedido` (
  `id_dest_ped` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `CalleNumero` varchar(100) DEFAULT NULL,
  `Colonia` varchar(100) DEFAULT NULL,
  `Ciudad` varchar(50) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Pais` varchar(50) DEFAULT NULL,
  `CodigoPostal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_embarque_fotos`
--

CREATE TABLE `th_embarque_fotos` (
  `id` int(11) NOT NULL,
  `th_embarque_folio` int(11) DEFAULT NULL,
  `folio_pedido` varchar(50) DEFAULT NULL,
  `ruta` varchar(500) DEFAULT NULL,
  `descripcion` varchar(40) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `foto` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_entalmacen`
--

CREATE TABLE `th_entalmacen` (
  `empresa_id` int(11) NOT NULL,
  `Fol_Folio` text DEFAULT NULL,
  `Cve_Almac` text DEFAULT NULL,
  `Fec_Entrada` text DEFAULT NULL,
  `Fol_OEP` text DEFAULT NULL,
  `Cve_Usuario` text DEFAULT NULL,
  `Cve_Proveedor` text DEFAULT NULL,
  `STATUS` text DEFAULT NULL,
  `Cve_Autorizado` text DEFAULT NULL,
  `tipo` text DEFAULT NULL,
  `BanCrossD` text DEFAULT NULL,
  `id_ocompra` text DEFAULT NULL,
  `placas` text DEFAULT NULL,
  `Fec_Factura_Prov` text DEFAULT NULL,
  `bufer` text DEFAULT NULL,
  `HoraInicio` text DEFAULT NULL,
  `ID_Protocolo` text DEFAULT NULL,
  `Consec_protocolo` text DEFAULT NULL,
  `cve_ubicacion` text DEFAULT NULL,
  `HoraFin` text DEFAULT NULL,
  `Fact_Prov` text DEFAULT NULL,
  `TipoCambioSAP` text DEFAULT NULL,
  `Id_moneda` text DEFAULT NULL,
  `Proveedor` text DEFAULT NULL,
  `Proyecto` text DEFAULT NULL,
  `Pedimento_Well` text DEFAULT NULL,
  `Referencia_Well` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_entalmacen_fotos`
--

CREATE TABLE `th_entalmacen_fotos` (
  `id` int(11) NOT NULL,
  `th_entalmacen_folio` int(11) NOT NULL,
  `ruta` text NOT NULL,
  `descripcion` varchar(40) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `foto` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_entalmacen_log`
--

CREATE TABLE `th_entalmacen_log` (
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `id` int(11) NOT NULL,
  `cve_usuario` varchar(255) DEFAULT NULL,
  `quehizo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_factura`
--

CREATE TABLE `th_factura` (
  `Id_Fac` int(11) NOT NULL,
  `Folio_Fac` varchar(50) DEFAULT NULL,
  `Tipo_Doc` varchar(50) DEFAULT NULL,
  `FechaEmision` date NOT NULL,
  `Cve_Clte` varchar(50) DEFAULT NULL,
  `Cve_Almacen` varchar(50) DEFAULT NULL,
  `OrdenCompra` varchar(50) DEFAULT NULL,
  `Fecha_Modif` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_incidencia`
--

CREATE TABLE `th_incidencia` (
  `ID_Incidencia` int(11) NOT NULL,
  `Fol_folio` varchar(20) DEFAULT NULL,
  `ReportadoCas` varchar(50) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Respuesta` text DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `clave` varchar(20) DEFAULT NULL,
  `centro_distribucion` varchar(100) DEFAULT NULL,
  `cliente` varchar(100) DEFAULT NULL,
  `reportador` varchar(100) DEFAULT NULL,
  `cargo_reportador` varchar(100) DEFAULT NULL,
  `responsable_recibo` varchar(100) DEFAULT NULL,
  `responsable_caso` varchar(50) DEFAULT NULL,
  `plan_accion` text DEFAULT NULL,
  `responsable_plan` varchar(100) DEFAULT NULL,
  `Fecha_accion` datetime DEFAULT NULL,
  `responsable_verificacion` varchar(100) DEFAULT NULL,
  `tipo_reporte` varchar(1) DEFAULT NULL,
  `id_motivo_registro` int(11) DEFAULT NULL,
  `desc_motivo_registro` text DEFAULT NULL,
  `id_motivo_cierre` int(11) DEFAULT NULL,
  `desc_motivo_cierre` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_inventario`
--

CREATE TABLE `th_inventario` (
  `ID_Inventario` int(11) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_almacen` varchar(45) DEFAULT NULL,
  `cve_zona` varchar(45) DEFAULT NULL,
  `Inv_Inicial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_ordenembarque`
--

CREATE TABLE `th_ordenembarque` (
  `ID_OEmbarque` int(11) NOT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `ID_Transporte` int(11) DEFAULT NULL,
  `Id_Ruta` int(11) DEFAULT NULL,
  `cve_usuario` varchar(20) DEFAULT NULL,
  `t_ubicacionembarque_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `FechaEnvio` datetime DEFAULT NULL,
  `destino` varchar(50) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `comentarios` varchar(50) DEFAULT NULL,
  `embarcador` varchar(50) DEFAULT NULL,
  `Num_Guia` varchar(20) DEFAULT NULL,
  `Tipo_Entrega` char(1) DEFAULT NULL,
  `Ban_Libre` char(1) DEFAULT NULL,
  `seguro` varchar(100) DEFAULT NULL,
  `flete` varchar(100) DEFAULT NULL,
  `origen` text DEFAULT NULL,
  `chofer` varchar(100) DEFAULT NULL,
  `Activo` int(11) NOT NULL,
  `guia_transporte` varchar(50) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `id_chofer` varchar(100) DEFAULT NULL,
  `num_unidad` varchar(100) DEFAULT NULL,
  `cve_transportadora` varchar(100) DEFAULT NULL,
  `placa` varchar(100) DEFAULT NULL,
  `sello_precinto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_pedido`
--

CREATE TABLE `th_pedido` (
  `Fol_folio` varchar(20) DEFAULT NULL,
  `Fec_Pedido` date DEFAULT NULL,
  `Cve_clte` varchar(20) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Fec_Entrega` date DEFAULT NULL,
  `cve_Vendedor` varchar(255) DEFAULT NULL,
  `Num_Meses` int(11) DEFAULT NULL,
  `Observaciones` text DEFAULT NULL,
  `statusaurora` int(11) DEFAULT NULL,
  `ID_Tipoprioridad` int(11) DEFAULT NULL,
  `Fec_Entrada` datetime DEFAULT NULL,
  `TipoPedido` varchar(50) DEFAULT NULL,
  `ruta` varchar(50) DEFAULT NULL,
  `bloqueado` tinyint(4) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `TipoDoc` varchar(50) DEFAULT NULL,
  `rango_hora` varchar(25) DEFAULT NULL,
  `cve_almac` varchar(20) DEFAULT NULL,
  `destinatario` varchar(20) DEFAULT NULL,
  `Id_Proveedor` int(11) DEFAULT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `Pick_Num` varchar(20) DEFAULT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Ship_Num` varchar(20) DEFAULT NULL,
  `BanEmpaque` char(1) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `id_pedido` int(11) NOT NULL,
  `Activo` int(11) DEFAULT NULL,
  `foto1` varchar(150) DEFAULT NULL,
  `foto2` varchar(150) DEFAULT NULL,
  `foto3` varchar(150) DEFAULT NULL,
  `foto4` varchar(150) DEFAULT NULL,
  `Forma_Pago` int(11) DEFAULT NULL,
  `tipo_venta` varchar(20) DEFAULT NULL,
  `tipo_negociacion` varchar(20) DEFAULT NULL,
  `Almac_Ori` varchar(59) DEFAULT NULL,
  `Docto_Ref` varchar(59) DEFAULT NULL,
  `Enviado` tinyint(1) DEFAULT NULL,
  `Ref_Wel` varchar(50) DEFAULT NULL,
  `Ref_Imp` varchar(50) DEFAULT NULL,
  `Pedimento` varchar(50) DEFAULT NULL,
  `Factura_Vta` varchar(50) DEFAULT NULL,
  `Ped_Imp` varchar(100) DEFAULT NULL,
  `Fec_Aprobado` datetime DEFAULT NULL,
  `Tot_Factura` decimal(18,2) DEFAULT NULL,
  `orden_etapa` int(11) DEFAULT NULL,
  `contacto_id` int(11) DEFAULT NULL,
  `tipo_asignacion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_pedido_ptl`
--

CREATE TABLE `th_pedido_ptl` (
  `Almacen` int(11) NOT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Fec_Req` datetime DEFAULT NULL,
  `Fec_Carga` datetime DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Secuencia` int(11) NOT NULL,
  `Prioridad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_pedservicios`
--

CREATE TABLE `th_pedservicios` (
  `id_pedido` int(11) NOT NULL,
  `Fol_Folio` varchar(50) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Fec_Pedido` date DEFAULT NULL,
  `Cve_clte` varchar(20) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `Fec_Inicio` date DEFAULT NULL,
  `Fec_Fin` date DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Observaciones` varchar(599) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Forma_Pago` int(11) DEFAULT NULL,
  `Docto_Ref` varchar(50) DEFAULT NULL,
  `Docto_Ped` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_ruta_surtido`
--

CREATE TABLE `th_ruta_surtido` (
  `idr` int(11) NOT NULL,
  `nombre` varchar(20) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_salalmacen`
--

CREATE TABLE `th_salalmacen` (
  `fol_folio` varchar(50) NOT NULL,
  `Tipo_Salida` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `fec_salida` datetime DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `Cve_Razon` int(11) DEFAULT NULL,
  `Cve_Ref1` varchar(50) DEFAULT NULL,
  `Cve_Ref2` varchar(50) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `RefFolio` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_secvisitas`
--

CREATE TABLE `th_secvisitas` (
  `Id` int(11) NOT NULL,
  `CodCli` varchar(50) DEFAULT NULL,
  `RutaId` int(11) NOT NULL,
  `Secuencia` int(11) DEFAULT NULL,
  `Dia` char(2) DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL,
  `IdVendedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `th_subpedido`
--

CREATE TABLE `th_subpedido` (
  `fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Sufijo` int(11) NOT NULL,
  `Fec_Entrada` datetime DEFAULT NULL,
  `Cve_Usuario` varchar(50) DEFAULT NULL,
  `Hora_inicio` datetime DEFAULT NULL,
  `Hora_Final` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Reviso` varchar(50) DEFAULT NULL,
  `nivel` int(11) DEFAULT NULL,
  `empaco` varchar(50) DEFAULT NULL,
  `cajaz_piezas` int(11) DEFAULT NULL,
  `buffer` varchar(50) DEFAULT NULL,
  `UltimaUbic` int(11) DEFAULT NULL,
  `TomarApartado` char(1) DEFAULT NULL,
  `HIR` datetime DEFAULT NULL,
  `HFR` datetime DEFAULT NULL,
  `HIE` datetime DEFAULT NULL,
  `HFE` datetime DEFAULT NULL,
  `Placas_T` varchar(50) DEFAULT NULL,
  `Embarco` varchar(50) DEFAULT NULL,
  `Chofer` varchar(100) DEFAULT NULL,
  `FI_Emp` datetime DEFAULT NULL,
  `FF_Emp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_almacen`
--

CREATE TABLE `tipo_almacen` (
  `id` int(11) NOT NULL,
  `clave_talmacen` varchar(20) DEFAULT NULL,
  `desc_tipo_almacen` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_transporte`
--

CREATE TABLE `tipo_transporte` (
  `id` int(11) NOT NULL,
  `clave_ttransporte` varchar(20) DEFAULT NULL,
  `alto` float(255,0) DEFAULT NULL,
  `fondo` float(255,0) DEFAULT NULL,
  `ancho` float(255,0) DEFAULT NULL,
  `capacidad_carga` int(11) DEFAULT NULL,
  `desc_ttransporte` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_dimprods`
--

CREATE TABLE `tmp_dimprods` (
  `id` int(11) NOT NULL,
  `﻿Clave` text DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Alto` double DEFAULT NULL,
  `Largo` double DEFAULT NULL,
  `Ancho` double DEFAULT NULL,
  `Peso` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_inventcicconteo`
--

CREATE TABLE `tmp_inventcicconteo` (
  `Id_Inventario` int(11) DEFAULT NULL,
  `NConteo` int(11) DEFAULT NULL,
  `Idy_Ubica` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `CantTeorica` decimal(18,3) DEFAULT NULL,
  `Cant1` decimal(18,3) DEFAULT NULL,
  `Cant2` decimal(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_inventconteo`
--

CREATE TABLE `tmp_inventconteo` (
  `Id_Inventario` int(11) DEFAULT NULL,
  `NConteo` int(11) DEFAULT NULL,
  `Idy_Ubica` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `CantTeorica` decimal(18,3) DEFAULT NULL,
  `Cant1` decimal(18,3) DEFAULT NULL,
  `Cant2` decimal(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_ordenentrada`
--

CREATE TABLE `tmp_ordenentrada` (
  `id` int(11) NOT NULL,
  `cve_rep` int(11) NOT NULL,
  `cve_art` varchar(20) DEFAULT NULL,
  `des_art` varchar(20) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_repkardexgral`
--

CREATE TABLE `tmp_repkardexgral` (
  `id` int(11) NOT NULL,
  `FECHA` datetime DEFAULT NULL,
  `ARTICULO` varchar(20) DEFAULT NULL,
  `NOMBRE` varchar(100) DEFAULT NULL,
  `LOTE` varchar(20) DEFAULT NULL,
  `caducidad` varchar(10) DEFAULT NULL,
  `CANTIDAD` int(11) DEFAULT NULL,
  `TIPO DE MOVIMIENTO` varchar(50) DEFAULT NULL,
  `ORIGEN` varchar(50) DEFAULT NULL,
  `DESTINO` varchar(50) DEFAULT NULL,
  `USUARIO` varchar(10) DEFAULT NULL,
  `MODO` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tmp_surtido`
--

CREATE TABLE `tmp_surtido` (
  `id` int(11) NOT NULL,
  `ubicacion` int(11) DEFAULT NULL,
  `almacen` int(11) DEFAULT NULL,
  `folio` varchar(15) DEFAULT NULL,
  `sufijo` int(11) DEFAULT NULL,
  `articulo` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tp_params`
--

CREATE TABLE `tp_params` (
  `cve_param` varchar(6) NOT NULL,
  `des_param` varchar(100) DEFAULT NULL,
  `val_string` varchar(255) DEFAULT NULL,
  `val_number` double DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trel_us_alm`
--

CREATE TABLE `trel_us_alm` (
  `cve_usuario` varchar(20) NOT NULL,
  `cve_almac` varchar(20) NOT NULL,
  `fecha_asignacion` datetime NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciacajas`
--

CREATE TABLE `ts_existenciacajas` (
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `PiezasXCaja` decimal(18,4) NOT NULL,
  `Id_Caja` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `nTarima` int(11) DEFAULT NULL,
  `Id_Pzs` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciacd`
--

CREATE TABLE `ts_existenciacd` (
  `Id` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_lote` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `cve_ubicacion` varchar(50) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciacdtarima`
--

CREATE TABLE `ts_existenciacdtarima` (
  `Id` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_lote` varchar(50) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `cve_ubicacion` varchar(50) DEFAULT NULL,
  `nTarima` int(11) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciapiezas`
--

CREATE TABLE `ts_existenciapiezas` (
  `cve_almac` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(18,4) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) DEFAULT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Cuarentena` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciatarima`
--

CREATE TABLE `ts_existenciatarima` (
  `cve_almac` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `lote` varchar(50) NOT NULL,
  `Fol_Folio` int(11) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `capcidad` int(11) NOT NULL,
  `existencia` decimal(18,4) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `Cuarentena` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_existenciaxart`
--

CREATE TABLE `ts_existenciaxart` (
  `cve_almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `num_existencia` double DEFAULT NULL,
  `num_apartado` double DEFAULT NULL,
  `num_stkmin` double DEFAULT NULL,
  `num_stkmax` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ts_ubicxart`
--

CREATE TABLE `ts_ubicxart` (
  `cve_articulo` varchar(50) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `CapacidadMinima` int(11) DEFAULT NULL,
  `CapacidadMaxima` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `folio` varchar(100) DEFAULT NULL,
  `caja_pieza` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tubicacionesretencion`
--

CREATE TABLE `tubicacionesretencion` (
  `id` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) NOT NULL,
  `cve_almacp` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `desc_ubicacion` varchar(255) DEFAULT NULL,
  `B_Devolucion` char(1) DEFAULT NULL,
  `AreaStagging` enum('N','S') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_aceptadosinventario`
--

CREATE TABLE `t_aceptadosinventario` (
  `ID_Inventario` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `NConteo` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_activo_fijo`
--

CREATE TABLE `t_activo_fijo` (
  `id` int(11) NOT NULL,
  `clave_activo` varchar(20) DEFAULT NULL,
  `id_articulo` int(11) NOT NULL,
  `id_orden_compra` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_serie` int(11) DEFAULT NULL,
  `nombre_empleado` varchar(50) DEFAULT NULL,
  `clave_empleado` varchar(20) DEFAULT NULL,
  `rfc_empleado` varchar(25) DEFAULT NULL,
  `fecha_entrada` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ajusteinventario`
--

CREATE TABLE `t_ajusteinventario` (
  `Id` int(11) NOT NULL,
  `Id_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Cant_Ant` decimal(18,3) DEFAULT NULL,
  `Cant_Ajuste` decimal(18,3) DEFAULT NULL,
  `Cve_Usuario_Inv` varchar(20) DEFAULT NULL,
  `Cve_Usuario_Ajuste` varchar(20) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_anexoacuse`
--

CREATE TABLE `t_anexoacuse` (
  `id` int(11) NOT NULL,
  `ID_Acuse` int(11) DEFAULT NULL,
  `FechaEntrega` datetime DEFAULT NULL,
  `Fol_folio` varchar(20) DEFAULT NULL,
  `RazonSocial` varchar(100) DEFAULT NULL,
  `FechaEnvio` datetime DEFAULT NULL,
  `Placas` varchar(20) DEFAULT NULL,
  `FechaEntrega1` datetime DEFAULT NULL,
  `Recivio` varchar(100) DEFAULT NULL,
  `NFactura` varchar(50) DEFAULT NULL,
  `ID_Incidencia` int(11) DEFAULT NULL,
  `urgencia` int(11) DEFAULT NULL,
  `orden` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_artcompuesto`
--

CREATE TABLE `t_artcompuesto` (
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_ArtComponente` varchar(50) NOT NULL,
  `Cantidad` double(18,5) NOT NULL,
  `Cantidad_Producida` double(18,5) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_umed` varchar(50) DEFAULT NULL,
  `Etapa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_bitacora`
--

CREATE TABLE `t_bitacora` (
  `Fecha` datetime DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `MODULO` varchar(50) DEFAULT NULL,
  `mensage` text DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `FechaFin` datetime DEFAULT NULL,
  `Referencia` varchar(50) NOT NULL,
  `BanAbierto` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_cajasreavastecimiento`
--

CREATE TABLE `t_cajasreavastecimiento` (
  `cve_almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `usuario` varchar(10) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `PiezasXCaja` int(11) NOT NULL,
  `ncajas` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_cardex`
--

CREATE TABLE `t_cardex` (
  `id` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `origen` varchar(50) DEFAULT NULL,
  `destino` varchar(50) DEFAULT NULL,
  `cantidad` decimal(18,4) DEFAULT NULL,
  `ajuste` decimal(18,4) DEFAULT NULL,
  `stockinicial` decimal(18,4) DEFAULT NULL,
  `id_TipoMovimiento` int(11) DEFAULT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `Cve_Almac` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Fec_Ingreso` date DEFAULT NULL,
  `Id_Motivo` int(11) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_clientexruta`
--

CREATE TABLE `t_clientexruta` (
  `id_clientexruta` int(11) NOT NULL,
  `clave_cliente` int(11) DEFAULT NULL,
  `clave_ruta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_codigocsd`
--

CREATE TABLE `t_codigocsd` (
  `id` int(11) NOT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `cve_zona` int(11) DEFAULT NULL,
  `cve_cia` int(11) DEFAULT NULL,
  `codigo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_colosurtido`
--

CREATE TABLE `t_colosurtido` (
  `Id_color` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `Rojo` int(11) DEFAULT NULL,
  `Verde` int(11) DEFAULT NULL,
  `Azul` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_configuracion`
--

CREATE TABLE `t_configuracion` (
  `id` int(11) NOT NULL,
  `SelImagen` tinyint(4) DEFAULT NULL,
  `Gen_DirImag` varchar(200) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_configuraciongeneral`
--

CREATE TABLE `t_configuraciongeneral` (
  `id` int(11) NOT NULL,
  `cve_conf` varchar(50) DEFAULT NULL,
  `Valor` text DEFAULT NULL,
  `id_almacen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_consecutivos_documentos`
--

CREATE TABLE `t_consecutivos_documentos` (
  `nombre` varchar(50) DEFAULT NULL,
  `numero` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `nombre_forma` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_consolidado`
--

CREATE TABLE `t_consolidado` (
  `Fol_Consolidado` varchar(20) NOT NULL,
  `Fol_PedidoCon` varchar(20) NOT NULL,
  `Fol_Folio` varchar(20) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_conteoinventario`
--

CREATE TABLE `t_conteoinventario` (
  `id` int(11) NOT NULL,
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `cve_usuario` varchar(20) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_supervisor` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_conteoinventariocicl`
--

CREATE TABLE `t_conteoinventariocicl` (
  `id` int(11) NOT NULL,
  `ID_PLAN` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `cve_usuario` varchar(20) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `cve_supervisor` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_dias_festivos`
--

CREATE TABLE `t_dias_festivos` (
  `id` int(11) NOT NULL,
  `Anio` int(11) NOT NULL,
  `Mes` int(11) NOT NULL,
  `Dia` int(11) NOT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_eda_sessions`
--

CREATE TABLE `t_eda_sessions` (
  `IdSession` int(11) NOT NULL,
  `Usuario` varchar(50) DEFAULT NULL,
  `IMEI` varchar(50) DEFAULT NULL,
  `Cve_Almac` varchar(50) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Expira` int(11) DEFAULT NULL,
  `Activo` bit(1) DEFAULT NULL,
  `Proceso` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_entalmacentransporte`
--

CREATE TABLE `t_entalmacentransporte` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` int(11) NOT NULL,
  `Operador` varchar(255) DEFAULT NULL,
  `No_Unidad` varchar(100) DEFAULT NULL,
  `Placas` varchar(50) DEFAULT NULL,
  `Linea_Transportista` varchar(100) DEFAULT NULL,
  `Observaciones` varchar(255) DEFAULT NULL,
  `Sello` varchar(100) DEFAULT NULL,
  `Fec_Ingreso` datetime DEFAULT NULL,
  `Fec_Salida` datetime DEFAULT NULL,
  `Id_Operador` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_errores`
--

CREATE TABLE `t_errores` (
  `id` int(11) NOT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `CometidoEn` varchar(20) DEFAULT NULL,
  `Descripcion` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_errorespedido`
--

CREATE TABLE `t_errorespedido` (
  `Fol_folio` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `Error_revisador` int(11) DEFAULT NULL,
  `Error_surtidor` char(10) DEFAULT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_existenciacajas_prod`
--

CREATE TABLE `t_existenciacajas_prod` (
  `Id` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Cve_CajaMix` int(11) DEFAULT NULL,
  `nTarima` int(11) DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `Id_Proveedor` int(11) DEFAULT NULL,
  `Cuarentena` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_existenciaptl`
--

CREATE TABLE `t_existenciaptl` (
  `ID` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_existenciaxdia`
--

CREATE TABLE `t_existenciaxdia` (
  `Fecha` date DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Existencia` decimal(18,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_guia`
--

CREATE TABLE `t_guia` (
  `id` int(11) NOT NULL,
  `Guia` decimal(18,0) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_historicoguias`
--

CREATE TABLE `t_historicoguias` (
  `Guia` varchar(50) NOT NULL,
  `Cve_Evento` varchar(50) NOT NULL,
  `Nivel_Hist` int(11) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Des_Evento` varchar(150) DEFAULT NULL,
  `Lugar_Evento` varchar(150) DEFAULT NULL,
  `Cve_Excepcion` varchar(20) DEFAULT NULL,
  `Des_Excepcion` varchar(250) DEFAULT NULL,
  `Detalles_Excepcion` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_impresion`
--

CREATE TABLE `t_impresion` (
  `ID_Impresion` int(11) NOT NULL,
  `etiqueta` text DEFAULT NULL,
  `IP` varchar(20) DEFAULT NULL,
  `Puerto` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_incidencias`
--

CREATE TABLE `t_incidencias` (
  `Fol_Folio` varchar(20) NOT NULL,
  `Fecha_Incidencia` datetime DEFAULT NULL,
  `Fecha_Solucion` datetime DEFAULT NULL,
  `Id_Caso` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Observaciones` varchar(400) DEFAULT NULL,
  `Cve_Error` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_indicadores`
--

CREATE TABLE `t_indicadores` (
  `Anio` int(11) NOT NULL,
  `Mes` int(11) NOT NULL,
  `Dia` int(11) NOT NULL,
  `Facturas Procesadas` int(11) NOT NULL,
  `Notas de Entrega Procesadas` int(11) NOT NULL,
  `Entregas Locales` decimal(6,2) NOT NULL,
  `Entregas Edo Mex` decimal(6,2) DEFAULT NULL,
  `Entregas Foraneas` decimal(6,2) NOT NULL,
  `Pedidos en Transito` decimal(6,2) NOT NULL,
  `Pedidos Atrasados` decimal(6,2) NOT NULL,
  `Pedidos Entregados` decimal(6,2) NOT NULL,
  `Errores ESTAFETA` int(11) DEFAULT NULL,
  `Errores SCI` int(11) DEFAULT NULL,
  `Errores Picking` int(11) DEFAULT NULL,
  `Efectividad Entrega` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_invcajas`
--

CREATE TABLE `t_invcajas` (
  `id_invcajas` int(11) NOT NULL,
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `PiezasXCaja` int(11) NOT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_invpiezas`
--

CREATE TABLE `t_invpiezas` (
  `id` bigint(20) NOT NULL,
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `ExistenciaTeorica` double DEFAULT NULL,
  `cve_usuario` varchar(20) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `ClaveEtiqueta` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Art_Cerrado` tinyint(4) NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `Cuarentena` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_invpiezasciclico`
--

CREATE TABLE `t_invpiezasciclico` (
  `ID_PLAN` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `ExistenciaTeorica` double DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `ClaveEtiqueta` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `Cuarentena` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_invtarima`
--

CREATE TABLE `t_invtarima` (
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Teorico` decimal(18,4) DEFAULT NULL,
  `existencia` double DEFAULT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Abierto` char(1) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `Cuarentena` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_invtarimaciclico`
--

CREATE TABLE `t_invtarimaciclico` (
  `ID_PLAN` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `cve_Lote` varchar(50) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Teorico` decimal(18,4) DEFAULT NULL,
  `existencia` double DEFAULT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Id_Proveedor` int(11) NOT NULL,
  `Abierto` char(1) DEFAULT NULL,
  `Cuarentena` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_license`
--

CREATE TABLE `t_license` (
  `empresa_id` int(11) NOT NULL,
  `id` text DEFAULT NULL,
  `L_Web` text DEFAULT NULL,
  `L_Mobile` text DEFAULT NULL,
  `Activo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_lista_diferencias`
--

CREATE TABLE `t_lista_diferencias` (
  `id` int(11) NOT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `Sufijo` char(1) DEFAULT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `LOTE` varchar(50) DEFAULT NULL,
  `PiexasXCaja` int(11) DEFAULT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `tipo` char(1) DEFAULT NULL,
  `modo` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_log`
--

CREATE TABLE `t_log` (
  `Fecha` datetime DEFAULT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `NCaja` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_log_operaciones`
--

CREATE TABLE `t_log_operaciones` (
  `empresa_id` int(11) NOT NULL,
  `modulo` text DEFAULT NULL,
  `usuario` text DEFAULT NULL,
  `fecha` text DEFAULT NULL,
  `operacion` text DEFAULT NULL,
  `dispositivo` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_dt` datetime GENERATED ALWAYS AS (coalesce(str_to_date(trim(`fecha`),'%Y-%m-%d %H:%i:%s'),str_to_date(trim(`fecha`),'%d/%m/%Y %H:%i:%s'),str_to_date(trim(`fecha`),'%Y-%m-%d'),str_to_date(trim(`fecha`),'%d/%m/%Y'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_log_sap`
--

CREATE TABLE `t_log_sap` (
  `id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `cadena` text DEFAULT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_log_ws`
--

CREATE TABLE `t_log_ws` (
  `Id` int(11) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Mensaje` varchar(500) DEFAULT NULL,
  `Respuesta` varchar(500) DEFAULT NULL,
  `Enviado` int(11) NOT NULL,
  `Proceso` varchar(100) DEFAULT NULL,
  `Dispositivo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_lotesxarticulo`
--

CREATE TABLE `t_lotesxarticulo` (
  `Fol_folio` varchar(20) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `LOTE` varchar(50) NOT NULL,
  `Num_cantidad` double DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_match`
--

CREATE TABLE `t_match` (
  `id` int(11) NOT NULL,
  `Cve_Almac` varchar(50) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Num_Cantidad` decimal(18,3) NOT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Cve_Proveedor` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_mensaje`
--

CREATE TABLE `t_mensaje` (
  `id` int(11) NOT NULL,
  `clave` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `mensaje` varchar(255) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_final` datetime NOT NULL,
  `activo` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_menu`
--

CREATE TABLE `t_menu` (
  `id_menu` bigint(20) NOT NULL,
  `modulo` varchar(200) DEFAULT NULL,
  `icono` varchar(30) DEFAULT NULL,
  `href` varchar(150) DEFAULT NULL,
  `id_menu_padre` decimal(18,0) DEFAULT NULL,
  `orden` tinyint(4) DEFAULT NULL,
  `orden_screen` int(11) DEFAULT NULL,
  `es_cliente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_menu__`
--

CREATE TABLE `t_menu__` (
  `id_menu` bigint(20) NOT NULL,
  `modulo` varchar(200) DEFAULT NULL,
  `icono` varchar(30) DEFAULT NULL,
  `href` varchar(150) DEFAULT NULL,
  `id_menu_padre` decimal(18,0) DEFAULT NULL,
  `orden` tinyint(4) DEFAULT NULL,
  `orden_screen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_monitoreoterminales`
--

CREATE TABLE `t_monitoreoterminales` (
  `id` int(11) NOT NULL,
  `idt` int(11) DEFAULT NULL,
  `tipo` char(1) DEFAULT NULL,
  `usuario` varchar(10) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `cadena` text DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_mon_no_tomados`
--

CREATE TABLE `t_mon_no_tomados` (
  `id` int(11) NOT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `cve_pasillo` varchar(3) DEFAULT NULL,
  `cve_rack` int(11) DEFAULT NULL,
  `Seccion` char(3) DEFAULT NULL,
  `cve_nivel` int(11) DEFAULT NULL,
  `Ubicacion` int(11) DEFAULT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `Sufijo` char(1) DEFAULT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `picking` char(1) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `MasBiejo` char(1) DEFAULT NULL,
  `revisado` char(1) DEFAULT NULL,
  `surtir` int(11) DEFAULT NULL,
  `hora` datetime DEFAULT NULL,
  `msg` text DEFAULT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_movcharolas`
--

CREATE TABLE `t_movcharolas` (
  `empresa_id` int(11) NOT NULL,
  `id` text DEFAULT NULL,
  `id_kardex` text DEFAULT NULL,
  `Cve_Almac` text DEFAULT NULL,
  `ID_Contenedor` text DEFAULT NULL,
  `Fecha` text DEFAULT NULL,
  `Origen` text DEFAULT NULL,
  `Destino` text DEFAULT NULL,
  `Id_TipoMovimiento` text DEFAULT NULL,
  `Cve_Usuario` text DEFAULT NULL,
  `Status` text DEFAULT NULL,
  `EsCaja` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_movcuarentena`
--

CREATE TABLE `t_movcuarentena` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `IdContenedor` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `PzsXCaja` int(11) DEFAULT NULL,
  `Fec_Ingreso` datetime DEFAULT NULL,
  `Id_MotivoIng` int(11) DEFAULT NULL,
  `Tipo_Cat_Ing` char(1) DEFAULT NULL,
  `Usuario_Ing` varchar(20) DEFAULT NULL,
  `Fec_Libera` datetime DEFAULT NULL,
  `Id_MotivoLib` int(11) DEFAULT NULL,
  `Tipo_Cat_Lib` char(1) DEFAULT NULL,
  `Usuario_Lib` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_mov_bajas`
--

CREATE TABLE `t_mov_bajas` (
  `id` int(11) NOT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `PiezasXCaja` int(11) DEFAULT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `tomadas` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_negados`
--

CREATE TABLE `t_negados` (
  `id` int(11) NOT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `Sufijo` char(1) DEFAULT NULL,
  `Cve_articulo` varchar(50) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ordenprod`
--

CREATE TABLE `t_ordenprod` (
  `id` int(11) NOT NULL,
  `Folio_Pro` varchar(20) NOT NULL,
  `cve_almac` varchar(255) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `Cant_Prod` int(11) DEFAULT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `FechaReg` datetime DEFAULT NULL,
  `Usr_Armo` varchar(255) DEFAULT NULL,
  `Hora_Ini` datetime DEFAULT NULL,
  `Hora_Fin` datetime DEFAULT NULL,
  `cronometro` varchar(10) DEFAULT NULL,
  `id_umed` int(11) DEFAULT NULL,
  `Status` varchar(1) DEFAULT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cve_Almac_Ori` varchar(50) DEFAULT NULL,
  `Tipo` varchar(20) DEFAULT NULL,
  `id_zona_almac` int(11) DEFAULT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `idy_ubica_dest` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_pedentregados`
--

CREATE TABLE `t_pedentregados` (
  `id` int(11) NOT NULL,
  `Fol_folio` varchar(20) DEFAULT NULL,
  `FechaEntrega` datetime DEFAULT NULL,
  `Cve_usuario` varchar(20) DEFAULT NULL,
  `Recibio` varchar(100) DEFAULT NULL,
  `NFactura` varchar(50) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Firma` blob DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_pedent_img`
--

CREATE TABLE `t_pedent_img` (
  `Id` int(11) NOT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Ref_Imagen` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_pedidostrasmision`
--

CREATE TABLE `t_pedidostrasmision` (
  `id` int(11) NOT NULL,
  `ARCHIVO` varchar(150) DEFAULT NULL,
  `FOL_FOLIO` varchar(20) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `Usuario` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_pendienteacomodo`
--

CREATE TABLE `t_pendienteacomodo` (
  `cve_articulo` varchar(50) DEFAULT NULL,
  `cve_lote` varchar(50) DEFAULT NULL,
  `Cantidad` double(18,3) DEFAULT NULL,
  `cve_ubicacion` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_perfilesusuarios`
--

CREATE TABLE `t_perfilesusuarios` (
  `empresa_id` int(11) NOT NULL,
  `ID_PERFIL` text DEFAULT NULL,
  `PER_NOMBRE` text DEFAULT NULL,
  `cve_cia` text DEFAULT NULL,
  `Activo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_permisos_perfil`
--

CREATE TABLE `t_permisos_perfil` (
  `id` int(11) NOT NULL,
  `ID_PERMISO` int(11) DEFAULT NULL,
  `ID_PERFIL` int(11) DEFAULT NULL,
  `STATUS` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_productoretenido`
--

CREATE TABLE `t_productoretenido` (
  `cve_ubicacion` varchar(20) NOT NULL,
  `fol_folio` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `cantidad` double DEFAULT NULL,
  `cve_almac` int(11) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_productotarima`
--

CREATE TABLE `t_productotarima` (
  `Fol_Folio` int(11) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Existencia` double DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_profiles`
--

CREATE TABLE `t_profiles` (
  `id_perfil` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `id_submenu` int(11) DEFAULT NULL,
  `id_role` int(11) NOT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_protocolo`
--

CREATE TABLE `t_protocolo` (
  `id` int(11) NOT NULL,
  `ID_Protocolo` varchar(20) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `FOLIO` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_rastreoguias`
--

CREATE TABLE `t_rastreoguias` (
  `Guia` varchar(50) NOT NULL,
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Serv_Status` varchar(50) DEFAULT NULL,
  `Fec_Recoleccion` datetime DEFAULT NULL,
  `Fec_Programada` datetime DEFAULT NULL,
  `Fec_Entrega` datetime DEFAULT NULL,
  `Recibe` varchar(150) DEFAULT NULL,
  `Destino` varchar(150) DEFAULT NULL,
  `Comentarios` varchar(400) DEFAULT NULL,
  `Datos_SAP` varchar(150) DEFAULT NULL,
  `Fecha_Act` datetime DEFAULT NULL,
  `Actualizado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_reabastecimiento`
--

CREATE TABLE `t_reabastecimiento` (
  `cve_almac` int(11) NOT NULL,
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Idy_Ubica` varchar(50) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Cve_lote` varchar(50) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `tomadas` int(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_recargastransmision`
--

CREATE TABLE `t_recargastransmision` (
  `Fol_Folio` varchar(50) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `Surtido` decimal(18,3) DEFAULT NULL,
  `Procesado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_recorrido_reavastecimiento`
--

CREATE TABLE `t_recorrido_reavastecimiento` (
  `cve_almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `usuario` varchar(10) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `Reabastecer` decimal(18,3) DEFAULT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Surtidas` decimal(18,3) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Existencia` decimal(18,3) DEFAULT NULL,
  `Folio` varchar(50) DEFAULT NULL,
  `Status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_recorrido_surtido`
--

CREATE TABLE `t_recorrido_surtido` (
  `idy_ubica` int(11) NOT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `cve_pasillo` varchar(3) DEFAULT NULL,
  `cve_rack` int(11) DEFAULT NULL,
  `Seccion` char(3) DEFAULT NULL,
  `cve_nivel` int(11) DEFAULT NULL,
  `Ubicacion` int(11) DEFAULT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `fol_folio` varchar(20) NOT NULL,
  `Sufijo` char(1) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `picking` char(1) DEFAULT NULL,
  `claverp` varchar(50) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `SURTIBLE` char(1) DEFAULT NULL,
  `OPCIONAL` char(1) DEFAULT NULL,
  `PiezasXCaja` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_registro_surtido`
--

CREATE TABLE `t_registro_surtido` (
  `idy_ubica` int(11) NOT NULL,
  `cve_almac` int(11) DEFAULT NULL,
  `cve_pasillo` varchar(3) DEFAULT NULL,
  `cve_rack` int(11) DEFAULT NULL,
  `Seccion` char(3) DEFAULT NULL,
  `cve_nivel` int(11) DEFAULT NULL,
  `Ubicacion` int(11) DEFAULT NULL,
  `orden_secuencia` int(11) DEFAULT NULL,
  `fol_folio` varchar(20) NOT NULL,
  `Sufijo` char(1) NOT NULL,
  `Cve_articulo` varchar(50) NOT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `picking` char(1) DEFAULT NULL,
  `claverp` varchar(50) DEFAULT NULL,
  `ClaveEtiqueta` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `SURTIBLE` char(1) DEFAULT NULL,
  `OPCIONAL` char(1) DEFAULT NULL,
  `PiezasXCaja` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_roles`
--

CREATE TABLE `t_roles` (
  `empresa_id` int(11) NOT NULL,
  `id_role` text DEFAULT NULL,
  `rol` text DEFAULT NULL,
  `activo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ruta`
--

CREATE TABLE `t_ruta` (
  `ID_Ruta` int(11) NOT NULL,
  `cve_ruta` varchar(20) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `status` enum('A','B','') DEFAULT NULL,
  `cve_almacenp` int(11) NOT NULL,
  `venta_preventa` int(11) NOT NULL,
  `control_pallets_cont` enum('S','N') DEFAULT NULL,
  `consig_pallets` int(11) DEFAULT NULL,
  `consig_cont` int(11) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_submenu`
--

CREATE TABLE `t_submenu` (
  `id_submenu` int(11) NOT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `id_opciones` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_submenu__`
--

CREATE TABLE `t_submenu__` (
  `id_submenu` int(11) NOT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `id_opciones` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_surtido_origen`
--

CREATE TABLE `t_surtido_origen` (
  `folio` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `web_apk` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_tarima`
--

CREATE TABLE `t_tarima` (
  `Id` int(11) NOT NULL,
  `ntarima` int(11) NOT NULL,
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Sufijo` int(11) NOT NULL,
  `cve_articulo` varchar(50) DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `cantidad` double DEFAULT NULL,
  `Num_Empacados` float DEFAULT NULL,
  `Caja_ref` int(11) DEFAULT NULL,
  `Ban_Embarcado` enum('S','N') DEFAULT NULL,
  `Abierta` tinyint(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_tipocontenedor`
--

CREATE TABLE `t_tipocontenedor` (
  `Cve_TipoCont` int(11) NOT NULL,
  `Des_TipoCont` varchar(250) DEFAULT NULL,
  `Ancho` int(11) DEFAULT NULL,
  `Largo` int(11) DEFAULT NULL,
  `Alto` int(11) DEFAULT NULL,
  `Peso` double(18,4) DEFAULT NULL,
  `CapVol` double(18,4) DEFAULT NULL,
  `PesoMax` double(18,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_tipomovimiento`
--

CREATE TABLE `t_tipomovimiento` (
  `empresa_id` int(11) NOT NULL,
  `id_TipoMovimiento` text DEFAULT NULL,
  `nombre` text DEFAULT NULL,
  `Activo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_tiposprioridad`
--

CREATE TABLE `t_tiposprioridad` (
  `ID_Tipoprioridad` int(11) NOT NULL,
  `Descripcion` char(50) DEFAULT NULL,
  `Prioridad` int(11) DEFAULT NULL,
  `Status` enum('A','B') DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `Clave` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_tipo_modulo`
--

CREATE TABLE `t_tipo_modulo` (
  `Id_Tipo` int(11) NOT NULL,
  `Des_Tipo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_transporte`
--

CREATE TABLE `t_transporte` (
  `id` int(11) NOT NULL,
  `ID_Transporte` varchar(20) DEFAULT NULL,
  `Nombre` varchar(20) DEFAULT NULL,
  `Placas` varchar(20) DEFAULT NULL,
  `cve_cia` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `tipo_transporte` varchar(20) DEFAULT NULL,
  `id_almac` int(11) DEFAULT NULL,
  `num_ec` varchar(20) DEFAULT NULL,
  `transporte_externo` int(11) DEFAULT NULL,
  `es_transportista` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_trazabilidad_existencias`
--

CREATE TABLE `t_trazabilidad_existencias` (
  `cve_almac` int(11) NOT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `cve_articulo` varchar(100) DEFAULT NULL,
  `cve_lote` varchar(100) DEFAULT NULL,
  `cantidad` float DEFAULT NULL,
  `ntarima` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `folio_entrada` int(11) DEFAULT NULL,
  `folio_oc` int(11) DEFAULT NULL,
  `factura_ent` varchar(100) DEFAULT NULL,
  `factura_oc` varchar(100) DEFAULT NULL,
  `proyecto` varchar(100) DEFAULT NULL,
  `id_tipo_movimiento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicacionembarque`
--

CREATE TABLE `t_ubicacionembarque` (
  `ID_Embarque` int(11) NOT NULL,
  `cve_ubicacion` varchar(20) NOT NULL,
  `cve_almac` int(11) NOT NULL,
  `status` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `AreaStagging` enum('S','N') DEFAULT NULL,
  `largo` float DEFAULT NULL,
  `ancho` float DEFAULT NULL,
  `alto` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicacionesainventariar`
--

CREATE TABLE `t_ubicacionesainventariar` (
  `ID_Inventario` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `status` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicaciones_revision`
--

CREATE TABLE `t_ubicaciones_revision` (
  `ID_URevision` int(11) NOT NULL,
  `cve_almac` varchar(255) NOT NULL,
  `cve_ubicacion` varchar(10) NOT NULL,
  `fol_folio` varchar(50) DEFAULT NULL,
  `sufijo` int(11) DEFAULT NULL,
  `Checado` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `descripcion` varchar(50) DEFAULT NULL,
  `AreaStagging` enum('N','S') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicacioninventario`
--

CREATE TABLE `t_ubicacioninventario` (
  `id` int(11) NOT NULL,
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `Cve_Usuario` varchar(20) DEFAULT NULL,
  `idy_ubica` int(11) DEFAULT NULL,
  `cve_ubicacion` varchar(11) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Vacia` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicacionvehiculo`
--

CREATE TABLE `t_ubicacionvehiculo` (
  `Id` int(11) NOT NULL,
  `Id_Vendedor` int(11) NOT NULL,
  `ID_Vehiculo` int(11) NOT NULL,
  `IdRuta` int(11) NOT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Longitud` float DEFAULT NULL,
  `Latitud` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_ubicacion_reabastecer`
--

CREATE TABLE `t_ubicacion_reabastecer` (
  `cve_articulo` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `faltantes` int(11) DEFAULT NULL,
  `cve_almac` int(11) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_urgencias`
--

CREATE TABLE `t_urgencias` (
  `Clave` int(11) NOT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_usuariosperfil`
--

CREATE TABLE `t_usuariosperfil` (
  `ID_PERFIL` int(11) NOT NULL,
  `cve_usuario` varchar(100) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_usu_alm_pre`
--

CREATE TABLE `t_usu_alm_pre` (
  `empresa_id` int(11) NOT NULL,
  `id` text DEFAULT NULL,
  `id_user` text DEFAULT NULL,
  `cve_almac` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_vendedores`
--

CREATE TABLE `t_vendedores` (
  `Id_Vendedor` int(11) NOT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `CalleNumero` varchar(250) DEFAULT NULL,
  `Colonia` varchar(250) DEFAULT NULL,
  `Ciudad` varchar(250) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Pais` varchar(50) DEFAULT NULL,
  `CodigoPostal` varchar(50) DEFAULT NULL,
  `Cve_Vendedor` varchar(20) NOT NULL,
  `Ban_Ayudante` tinyint(1) NOT NULL,
  `Psswd_EDA` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_webconfig`
--

CREATE TABLE `t_webconfig` (
  `id` int(11) NOT NULL,
  `servidor` varchar(100) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `ContraseÃƒÆ’Ã‚Â±a` varchar(100) DEFAULT NULL,
  `puerto` int(11) DEFAULT NULL,
  `Asunto` varchar(100) DEFAULT NULL,
  `Mensaje` text DEFAULT NULL,
  `Destinatario` text DEFAULT NULL,
  `CC` text DEFAULT NULL,
  `BCC` text DEFAULT NULL,
  `Id_Mail` int(11) NOT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_zonapaqueteria`
--

CREATE TABLE `t_zonapaqueteria` (
  `Id_Paqueteria` int(11) NOT NULL,
  `Cod_DANE` int(11) NOT NULL,
  `Municipio` varchar(255) NOT NULL,
  `Estado` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `t_zonasestafeta`
--

CREATE TABLE `t_zonasestafeta` (
  `CP_Inicial` varchar(20) DEFAULT NULL,
  `CP_Final` varchar(20) DEFAULT NULL,
  `Dias` int(11) DEFAULT NULL,
  `Zona` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacionesinventareadas`
--

CREATE TABLE `ubicacionesinventareadas` (
  `ID_PLAN` int(11) NOT NULL,
  `FECHA_APLICA` datetime NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `almacen` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `Cantidad` decimal(18,3) DEFAULT NULL,
  `status` char(10) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL,
  `NConteo` int(11) NOT NULL,
  `Cve_Usuario` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `emails_allowed` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `settings_company` varchar(255) DEFAULT NULL,
  `settings_description` text DEFAULT NULL,
  `settings_press_contact` varchar(255) DEFAULT NULL,
  `settings_logo` text DEFAULT NULL,
  `settings_primary_color` varchar(255) DEFAULT NULL,
  `settings_secondary_color` varchar(255) DEFAULT NULL,
  `timestamp` int(11) NOT NULL,
  `subdomain` text DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users_bitacora`
--

CREATE TABLE `users_bitacora` (
  `id` int(11) NOT NULL,
  `cve_usuario` varchar(200) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `IP_Address` varchar(100) DEFAULT NULL,
  `cve_almacen` varchar(50) DEFAULT NULL,
  `sesion_cerrada` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users_online`
--

CREATE TABLE `users_online` (
  `IdSesion` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `last_updated` datetime NOT NULL,
  `IP_Address` varchar(50) DEFAULT NULL,
  `Expira` int(11) NOT NULL,
  `Multisesion` tinyint(1) DEFAULT NULL,
  `Activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vd_inventariociclico`
--

CREATE TABLE `vd_inventariociclico` (
  `ID_PLAN` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `idy_ubica` int(11) NOT NULL,
  `ntarima` bigint(20) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `PiezasXCaja` bigint(20) NOT NULL,
  `Cantidad` double DEFAULT NULL,
  `cve_usuario` varchar(50) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `Id` int(11) NOT NULL,
  `RutaId` int(11) DEFAULT NULL,
  `VendedorId` int(11) NOT NULL,
  `CodCliente` varchar(50) DEFAULT NULL,
  `Documento` varchar(50) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `TipoVta` varchar(50) DEFAULT NULL,
  `DiasCred` int(11) DEFAULT NULL,
  `CreditoDispo` decimal(18,2) DEFAULT NULL,
  `Saldo` decimal(18,2) DEFAULT NULL,
  `Fvence` date DEFAULT NULL,
  `SubTotal` decimal(10,2) DEFAULT NULL,
  `IVA` decimal(10,2) DEFAULT NULL,
  `IEPS` decimal(10,2) DEFAULT NULL,
  `TOTAL` decimal(10,2) DEFAULT NULL,
  `EnLetra` varchar(400) DEFAULT NULL,
  `Items` decimal(18,2) DEFAULT NULL,
  `FormaPag` int(11) DEFAULT NULL,
  `DocSalida` varchar(50) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `Cancelada` smallint(6) DEFAULT NULL,
  `Kg` decimal(18,2) DEFAULT NULL,
  `ID_Ayudante1` int(11) DEFAULT NULL,
  `ID_Ayudante2` int(11) DEFAULT NULL,
  `VenAyunate` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vh_pedido_ola`
--

CREATE TABLE `vh_pedido_ola` (
  `Fol_folio` varchar(20) DEFAULT NULL,
  `Fec_Pedido` date DEFAULT NULL,
  `Cve_clte` varchar(20) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `Fec_Entrega` date DEFAULT NULL,
  `cve_Vendedor` varchar(255) DEFAULT NULL,
  `Num_Meses` int(11) DEFAULT NULL,
  `Observaciones` mediumtext DEFAULT NULL,
  `ID_Tipoprioridad` int(11) DEFAULT NULL,
  `Fec_Entrada` datetime DEFAULT NULL,
  `TipoPedido` varchar(50) DEFAULT NULL,
  `bloqueado` tinyint(4) DEFAULT NULL,
  `cve_almac` varchar(20) DEFAULT NULL,
  `Pick_Num` varchar(20) DEFAULT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Ship_Num` varchar(20) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas`
--

CREATE TABLE `visitas` (
  `Id` int(11) NOT NULL,
  `CodCliente` varchar(50) DEFAULT NULL,
  `DiaO` int(11) DEFAULT NULL,
  `FechaI` datetime DEFAULT NULL,
  `EnSecuencia` tinyint(1) DEFAULT NULL,
  `FechaF` datetime DEFAULT NULL,
  `Venta` decimal(18,0) DEFAULT NULL,
  `Pedido` decimal(18,0) DEFAULT NULL,
  `Devolucion` decimal(18,0) DEFAULT NULL,
  `Cobranza` decimal(18,0) DEFAULT NULL,
  `IdCe` decimal(18,0) DEFAULT NULL,
  `Cve_Ruta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_apartadoparasurtido`
--

CREATE TABLE `vs_apartadoparasurtido` (
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Apartadas` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existencialotexubica`
--

CREATE TABLE `vs_existencialotexubica` (
  `Cve_Almac` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Existencia` decimal(18,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existenciaparasurtido`
--

CREATE TABLE `vs_existenciaparasurtido` (
  `Cve_Almac` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` double DEFAULT NULL,
  `BanPTL` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existenciaparasurtidoconcentrado`
--

CREATE TABLE `vs_existenciaparasurtidoconcentrado` (
  `Cve_Almac` int(11) NOT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `Existencia` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existenciaparasurtidodrivein`
--

CREATE TABLE `vs_existenciaparasurtidodrivein` (
  `Cve_Almac` int(11) NOT NULL,
  `Idy_Ubica` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` decimal(65,4) DEFAULT NULL,
  `BanPTL` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existenciaseriexubica`
--

CREATE TABLE `vs_existenciaseriexubica` (
  `Cve_Almac` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Serie` varchar(50) NOT NULL,
  `Existencia` decimal(18,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_existparasurtidotras`
--

CREATE TABLE `vs_existparasurtidotras` (
  `Cve_Almac` int(11) NOT NULL,
  `Idy_Ubica` varchar(20) DEFAULT NULL,
  `cve_articulo` varchar(50) NOT NULL,
  `cve_lote` varchar(50) NOT NULL,
  `Existencia` double DEFAULT NULL,
  `BanPTL` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_inventarioxubic`
--

CREATE TABLE `vs_inventarioxubic` (
  `ID_Inventario` int(11) NOT NULL,
  `NConteo` int(11) NOT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Cantidad` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_lpmanufacturaot`
--

CREATE TABLE `vs_lpmanufacturaot` (
  `Folio_Pro` varchar(20) NOT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cve_Almac` varchar(255) DEFAULT NULL,
  `Idy_Ubica` int(11) NOT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `nTarima` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) NOT NULL,
  `Lote` varchar(50) NOT NULL,
  `Existencia` decimal(18,4) DEFAULT NULL,
  `CveLP` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vs_lp_en_ot`
--

CREATE TABLE `vs_lp_en_ot` (
  `Folio_Pro` varchar(20) NOT NULL,
  `Referencia` varchar(50) DEFAULT NULL,
  `Cve_Almac` varchar(255) DEFAULT NULL,
  `ID_Proveedor` int(11) DEFAULT NULL,
  `nTarima` int(11) NOT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cve_Lote` varchar(50) DEFAULT NULL,
  `CveLP` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_almacen_compat`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_almacen_compat` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_bi_eficiencia_entrada`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_bi_eficiencia_entrada` (
`fecha` date
,`Cve_Almac` text
,`cve_proyecto` mediumtext
,`cve_proveedor` mediumtext
,`cve_usuario` text
,`movs` bigint(21)
,`uds_movidas` double
,`horas_trab` decimal(45,2)
,`movs_por_hora` decimal(27,2)
,`uds_por_hora` double(19,2)
,`tmi_min_prom` decimal(22,1)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_bi_lote_proyecto`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_bi_lote_proyecto` (
`cve_articulo` mediumtext
,`lote` mediumtext
,`ts_primera_entrada` mediumtext
,`cve_proyecto` mediumtext
,`cve_proveedor` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_bi_proveedor`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_bi_proveedor` (
`cve_proveedor` longtext
,`des_proveedor` longtext
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_bi_proyecto`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_bi_proyecto` (
`cve_proyecto` longtext
,`des_proyecto` longtext
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cajamguia`
--

CREATE TABLE `v_cajamguia` (
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Fec_Pedido` date DEFAULT NULL,
  `cve_almac` varchar(20) DEFAULT NULL,
  `Guia` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cajamixta`
--

CREATE TABLE `v_cajamixta` (
  `Cve_CajaMix` int(11) NOT NULL,
  `Fol_Folio` varchar(20) DEFAULT NULL,
  `Sufijo` int(11) DEFAULT NULL,
  `NCaja` int(11) DEFAULT NULL,
  `Cve_TipoCaja` int(11) DEFAULT NULL,
  `Abierta` char(1) DEFAULT NULL,
  `Cve_Articulo` varchar(50) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `Cve_lote` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cantidadvsexistenciaproduccion`
--

CREATE TABLE `v_cantidadvsexistenciaproduccion` (
  `orden_id` varchar(50) DEFAULT NULL,
  `cod_art_compuesto` varchar(50) DEFAULT NULL,
  `clave` varchar(55) DEFAULT NULL,
  `control_lotes` varchar(1) NOT NULL,
  `Lote` varchar(50) DEFAULT NULL,
  `LoteOT` varchar(50) DEFAULT NULL,
  `Caduca` varchar(1) NOT NULL,
  `Caducidad` varchar(10) DEFAULT NULL,
  `control_peso` varchar(1) NOT NULL,
  `Cantidad` double(22,9) DEFAULT NULL,
  `Cant_OT` bigint(20) DEFAULT NULL,
  `cantnecesaria` double DEFAULT NULL,
  `Cantidad_Producida` double(22,5) DEFAULT NULL,
  `ubicacion` varchar(11) DEFAULT NULL,
  `existencia` decimal(50,4) NOT NULL,
  `um` varchar(20) DEFAULT NULL,
  `mav_cveunimed` varchar(20) DEFAULT NULL,
  `clave_almacen` varchar(20) DEFAULT NULL,
  `Cve_Contenedor` varchar(70) DEFAULT NULL,
  `CveLP` varchar(70) NOT NULL,
  `Id_Contenedor` bigint(20) NOT NULL,
  `acepto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cant_devuelta`
--

CREATE TABLE `v_cant_devuelta` (
  `Fol_Folio` varchar(50) DEFAULT NULL,
  `Cve_Articulo` varchar(100) DEFAULT NULL,
  `Cve_Lote` varchar(10) DEFAULT NULL,
  `Cantidad` decimal(40,4) DEFAULT NULL,
  `Cant_Devuelta` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_clientedestinatario`
--

CREATE TABLE `v_clientedestinatario` (
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `Cve_CteProv` varchar(20) DEFAULT NULL,
  `Id_Destinatario` int(11) NOT NULL,
  `RazonSocial` varchar(100) DEFAULT NULL,
  `Destinatario` varchar(255) DEFAULT NULL,
  `Direccion` varchar(255) DEFAULT NULL,
  `Colonia` varchar(255) DEFAULT NULL,
  `CP` varchar(255) DEFAULT NULL,
  `Ciudad` varchar(255) DEFAULT NULL,
  `Estado` varchar(255) DEFAULT NULL,
  `Pais` varchar(50) DEFAULT NULL,
  `RFC` varchar(100) DEFAULT NULL,
  `Telefono1` varchar(100) DEFAULT NULL,
  `Limite_Credito` float(18,2) NOT NULL,
  `Dias_Credito` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_clientesruta`
--

CREATE TABLE `v_clientesruta` (
  `Cve_Clte` varchar(20) DEFAULT NULL,
  `RazonSocial` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `Pais` varchar(50) DEFAULT NULL,
  `postal` varchar(255) DEFAULT NULL,
  `RFC` varchar(100) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `Telefono2` varchar(100) DEFAULT NULL,
  `CondicionPago` varchar(30) DEFAULT NULL,
  `longitud` text DEFAULT NULL,
  `latitud` text DEFAULT NULL,
  `credito` int(11) DEFAULT NULL,
  `limite_credito` float(18,2) DEFAULT NULL,
  `dias_credito` int(11) DEFAULT NULL,
  `credito_actual` float(18,2) DEFAULT NULL,
  `saldo_inicial` float(18,2) DEFAULT NULL,
  `saldo_actual` float(18,2) DEFAULT NULL,
  `validar_gps` int(11) DEFAULT NULL,
  `Id_Fcm` varchar(255) DEFAULT NULL,
  `Ruta` varchar(20) DEFAULT NULL,
  `IdRuta` int(11) DEFAULT NULL,
  `IdEmpresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cobranza_aging`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cobranza_aging` (
`IdEmpresa` varchar(50)
,`Cliente` int(11)
,`TotalSaldo` decimal(32,0)
,`Bucket_0` decimal(32,0)
,`Bucket_1_30` decimal(32,0)
,`Bucket_31_60` decimal(32,0)
,`Bucket_61_90` decimal(32,0)
,`Bucket_91_120` decimal(32,0)
,`Bucket_121_Mas` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cobranza_analitico`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cobranza_analitico` (
`id` int(11)
,`IdEmpresa` varchar(50)
,`IdDestinatario` int(11)
,`id_destinatario` int(11)
,`Cve_Clte` varchar(20)
,`id_cliente` int(11)
,`RazonSocial` varchar(100)
,`RazonComercial` varchar(100)
,`Documento` varchar(50)
,`TipoDoc` varchar(50)
,`FolioInterno` int(11)
,`Saldo` decimal(10,0)
,`Status` int(11)
,`RutaId` int(11)
,`RutaDescripcion` varchar(50)
,`UltPago` varchar(50)
,`FechaReg` datetime
,`FechaVence` datetime
,`DiaO` int(11)
,`DiasAtraso` int(7)
,`EstatusTexto` varchar(15)
,`RangoAntiguedad` varchar(10)
,`PeriodoMes` varchar(7)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cobranza_resumen_empresa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cobranza_resumen_empresa` (
`IdEmpresa` varchar(50)
,`Documentos` bigint(21)
,`TotalSaldo` decimal(32,0)
,`CarteraVencida` decimal(32,0)
,`CarteraVigente` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_cons_devctes`
--

CREATE TABLE `v_cons_devctes` (
  `Folio_Pedido` varchar(50) DEFAULT NULL,
  `Cve_Articulo` varchar(100) DEFAULT NULL,
  `Cve_Lote` varchar(10) DEFAULT NULL,
  `Cant_Devuelta` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_existencia_proyecto`
--

CREATE TABLE `v_existencia_proyecto` (
  `empresa_id` int(11) NOT NULL,
  `Cve_Almac` text DEFAULT NULL,
  `Idy_Ubica` text DEFAULT NULL,
  `nTarima` text DEFAULT NULL,
  `Cve_Articulo` text DEFAULT NULL,
  `Cve_Lote` text DEFAULT NULL,
  `Existencia` text DEFAULT NULL,
  `Proyecto` text DEFAULT NULL,
  `ID_Proveedor` text DEFAULT NULL,
  `Cuarentena` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_kardex_doble_partida`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_kardex_doble_partida` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_kardex_enriquecido`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_kardex_enriquecido` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_kardex_enriquecido_v2`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_kardex_enriquecido_v2` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_log_operaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_log_operaciones` (
`empresa_id` int(11)
,`modulo` mediumtext
,`usuario` mediumtext
,`operacion` mediumtext
,`dispositivo` mediumtext
,`observaciones` mediumtext
,`fecha_dt` datetime
,`fecha_raw` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `v_pendientesacomodo`
--

CREATE TABLE `v_pendientesacomodo` (
  `cve_articulo` varchar(50) NOT NULL,
  `Cve_Lote` varchar(50) NOT NULL,
  `Cantidad` decimal(41,4) DEFAULT NULL,
  `Cve_Ubicacion` varchar(20) DEFAULT NULL,
  `ID_Proveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_productividad_kardex_almacen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_productividad_kardex_almacen` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_productividad_kardex_daily`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_productividad_kardex_daily` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_productividad_kardex_topprod`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_productividad_kardex_topprod` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_productividad_kardex_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_productividad_kardex_usuario` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_stg_bom_actual`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_stg_bom_actual` (
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_usuario_compat`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_usuario_compat` (
`id_usuario` bigint(11)
,`nombre` mediumtext
,`cve_usuario` varchar(20)
,`email` varchar(255)
,`perfil` varchar(255)
,`Activo` int(11)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_c_sgpoarticulo_old`
--

CREATE TABLE `_c_sgpoarticulo_old` (
  `cve_sgpoart` int(11) NOT NULL,
  `cve_gpoart` int(11) NOT NULL,
  `des_sgpoart` varchar(100) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_c_ssgpoarticulo_old`
--

CREATE TABLE `_c_ssgpoarticulo_old` (
  `cve_ssgpoart` int(11) NOT NULL,
  `cve_sgpoart` int(11) NOT NULL,
  `des_ssgpoart` varchar(200) DEFAULT NULL,
  `Opcinal` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_c_tipocaja_old`
--

CREATE TABLE `_c_tipocaja_old` (
  `cve_tipcaja` int(11) NOT NULL,
  `des_tipcaja` varchar(100) DEFAULT NULL,
  `num_largo` decimal(19,4) DEFAULT NULL,
  `num_ancho` decimal(19,4) DEFAULT NULL,
  `num_alto` decimal(19,4) DEFAULT NULL,
  `Ban_Picking` char(1) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_td_aduana_old`
--

CREATE TABLE `_td_aduana_old` (
  `ID_Aduana` int(11) NOT NULL,
  `cve_articulo` varchar(20) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `cve_lote` varchar(20) NOT NULL,
  `caducidad` datetime DEFAULT NULL,
  `temperatura` varchar(20) DEFAULT NULL,
  `num_orden` varchar(20) DEFAULT NULL,
  `Ingresado` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_th_aduana_old`
--

CREATE TABLE `_th_aduana_old` (
  `ID_Aduana` int(11) NOT NULL,
  `num_pedimento` varchar(20) DEFAULT NULL,
  `fech_pedimento` datetime DEFAULT NULL,
  `aduana` varchar(25) DEFAULT NULL,
  `factura` varchar(20) DEFAULT NULL,
  `fech_llegPed` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `ID_Proveedor` int(11) NOT NULL,
  `ID_Protocolo` varchar(10) DEFAULT NULL,
  `Consec_protocolo` int(11) DEFAULT NULL,
  `cve_usuario` varchar(10) DEFAULT NULL,
  `Cve_Almac` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_th_entalmacen`
--

CREATE TABLE `_th_entalmacen` (
  `Fol_Folio` int(11) NOT NULL,
  `Cve_Almac` int(11) NOT NULL,
  `Fec_Entrada` datetime DEFAULT NULL,
  `fol_oep` varchar(20) DEFAULT NULL,
  `Cve_Usuario` varchar(10) DEFAULT NULL,
  `Cve_Proveedor` int(11) DEFAULT NULL,
  `STATUS` char(1) DEFAULT NULL,
  `Cve_Autorizado` char(10) DEFAULT NULL,
  `TieneOE` char(1) DEFAULT NULL,
  `statusaurora` int(11) DEFAULT NULL,
  `id_ocompra` int(11) DEFAULT NULL,
  `placas` varchar(20) DEFAULT NULL,
  `entarimado` char(1) DEFAULT NULL,
  `bufer` varchar(20) DEFAULT NULL,
  `HoraInicio` datetime DEFAULT NULL,
  `ID_Protocolo` varchar(10) DEFAULT NULL,
  `Consec_protocolo` int(11) DEFAULT NULL,
  `cve_ubicacion` varchar(20) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_ts_ubicxart_old`
--

CREATE TABLE `_ts_ubicxart_old` (
  `cve_articulo` varchar(20) DEFAULT NULL,
  `idy_ubica` int(11) NOT NULL,
  `CapacidadMinima` int(11) DEFAULT NULL,
  `CapacidadMaxima` int(11) DEFAULT NULL,
  `Activo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `__vt_monitoreo`
--

CREATE TABLE `__vt_monitoreo` (
  `id` bigint(20) NOT NULL,
  `id_pedido` varchar(20) DEFAULT NULL,
  `fol_folio` varchar(20) DEFAULT NULL,
  `fec_pedido` datetime DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `descripcion` char(100) DEFAULT NULL,
  `postal` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `guia` varchar(50) DEFAULT NULL,
  `fec_recoleccion` datetime DEFAULT NULL,
  `fec_entrega` datetime DEFAULT NULL,
  `recibe` varchar(150) DEFAULT NULL,
  `serv_status` varchar(50) DEFAULT NULL,
  `hora_inicio` datetime DEFAULT NULL,
  `hir` datetime DEFAULT NULL,
  `fi_emp` datetime DEFAULT NULL,
  `hie` datetime DEFAULT NULL,
  `u_asig` varchar(255) DEFAULT NULL,
  `u_empa` varchar(255) DEFAULT NULL,
  `u_revi` varchar(255) DEFAULT NULL,
  `guia_caja` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_almacen_compat`
--
DROP TABLE IF EXISTS `v_almacen_compat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_almacen_compat`  AS SELECT `c_almacenp`.`empresa_id` AS `empresa_id`, `c_almacenp`.`id` AS `id`, `c_almacenp`.`clave` AS `clave`, `c_almacenp`.`nombre` AS `des_almac`, coalesce(`c_almacenp`.`BL`,'') AS `zona` FROM `c_almacenp` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_bi_eficiencia_entrada`
--
DROP TABLE IF EXISTS `v_bi_eficiencia_entrada`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bi_eficiencia_entrada`  AS SELECT cast(coalesce(`h`.`Fec_Entrada`,`d`.`fecha_inicio`) as date) AS `fecha`, `h`.`Cve_Almac` AS `Cve_Almac`, cast(trim(`h`.`Proyecto`) as char charset utf8mb4) AS `cve_proyecto`, cast(trim(`h`.`Cve_Proveedor`) as char charset utf8mb4) AS `cve_proveedor`, `h`.`Cve_Usuario` AS `cve_usuario`, count(0) AS `movs`, sum(coalesce(`d`.`CantidadRecibida`,0)) AS `uds_movidas`, round(sum(greatest(timestampdiff(SECOND,coalesce(`d`.`fecha_inicio`,`h`.`HoraInicio`),coalesce(`d`.`fecha_fin`,`h`.`HoraFin`)),0)) / 3600,2) AS `horas_trab`, round(count(0) / nullif(sum(greatest(timestampdiff(SECOND,coalesce(`d`.`fecha_inicio`,`h`.`HoraInicio`),coalesce(`d`.`fecha_fin`,`h`.`HoraFin`)),0)) / 3600,0),2) AS `movs_por_hora`, round(sum(coalesce(`d`.`CantidadRecibida`,0)) / nullif(sum(greatest(timestampdiff(SECOND,coalesce(`d`.`fecha_inicio`,`h`.`HoraInicio`),coalesce(`d`.`fecha_fin`,`h`.`HoraFin`)),0)) / 3600,0),2) AS `uds_por_hora`, round(avg(greatest(timestampdiff(SECOND,coalesce(`d`.`fecha_inicio`,`h`.`HoraInicio`),coalesce(`d`.`fecha_fin`,`h`.`HoraFin`)),0)) / 60,1) AS `tmi_min_prom` FROM (`th_entalmacen` `h` left join `td_entalmacen` `d` on(`d`.`empresa_id` = `h`.`empresa_id` and trim(`d`.`fol_folio`) = trim(`h`.`Fol_Folio`))) GROUP BY cast(coalesce(`h`.`Fec_Entrada`,`d`.`fecha_inicio`) as date), `h`.`Cve_Almac`, cast(trim(`h`.`Proyecto`) as char charset utf8mb4), `h`.`Cve_Proveedor`, `h`.`Cve_Usuario` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_bi_lote_proyecto`
--
DROP TABLE IF EXISTS `v_bi_lote_proyecto`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bi_lote_proyecto`  AS SELECT cast(trim(`d`.`cve_articulo`) as char charset utf8mb4) AS `cve_articulo`, cast(trim(`d`.`cve_lote`) as char charset utf8mb4) AS `lote`, min(coalesce(`d`.`fecha_inicio`,`h`.`HoraInicio`,`h`.`Fec_Entrada`)) AS `ts_primera_entrada`, cast(trim(`h`.`Proyecto`) as char charset utf8mb4) AS `cve_proyecto`, cast(trim(`h`.`Cve_Proveedor`) as char charset utf8mb4) AS `cve_proveedor` FROM (`th_entalmacen` `h` join `td_entalmacen` `d` on(trim(`d`.`fol_folio`) = trim(`h`.`Fol_Folio`))) WHERE coalesce(`d`.`cve_lote`,'') <> '' GROUP BY cast(trim(`d`.`cve_articulo`) as char charset utf8mb4), cast(trim(`d`.`cve_lote`) as char charset utf8mb4), cast(trim(`h`.`Proyecto`) as char charset utf8mb4), cast(trim(`h`.`Cve_Proveedor`) as char charset utf8mb4) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_bi_proveedor`
--
DROP TABLE IF EXISTS `v_bi_proveedor`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bi_proveedor`  AS SELECT cast(`lp`.`cve_proveedor` as char charset utf8mb4) AS `cve_proveedor`, cast(`lp`.`cve_proveedor` as char charset utf8mb4) AS `des_proveedor` FROM `v_bi_lote_proyecto` AS `lp` WHERE coalesce(`lp`.`cve_proveedor`,'') <> '' GROUP BY 1, 2 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_bi_proyecto`
--
DROP TABLE IF EXISTS `v_bi_proyecto`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bi_proyecto`  AS SELECT cast(`p`.`Cve_Proyecto` as char charset utf8mb4) AS `cve_proyecto`, cast(`p`.`Des_Proyecto` as char charset utf8mb4) AS `des_proyecto` FROM `c_proyecto` AS `p`union select cast(`lp`.`cve_proyecto` as char charset utf8mb4) AS `Name_exp_1`,cast(`lp`.`cve_proyecto` as char charset utf8mb4) AS `Name_exp_2` from `v_bi_lote_proyecto` `lp` where coalesce(`lp`.`cve_proyecto`,'') <> ''  ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cobranza_aging`
--
DROP TABLE IF EXISTS `v_cobranza_aging`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cobranza_aging`  AS SELECT `c`.`IdEmpresa` AS `IdEmpresa`, `c`.`Cliente` AS `Cliente`, sum(coalesce(`c`.`Saldo`,0)) AS `TotalSaldo`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) <= 0 then `c`.`Saldo` else 0 end) AS `Bucket_0`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) between 1 and 30 then `c`.`Saldo` else 0 end) AS `Bucket_1_30`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) between 31 and 60 then `c`.`Saldo` else 0 end) AS `Bucket_31_60`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) between 61 and 90 then `c`.`Saldo` else 0 end) AS `Bucket_61_90`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) between 91 and 120 then `c`.`Saldo` else 0 end) AS `Bucket_91_120`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and to_days(curdate()) - to_days(`c`.`FechaVence`) > 120 then `c`.`Saldo` else 0 end) AS `Bucket_121_Mas` FROM `cobranza` AS `c` GROUP BY `c`.`IdEmpresa`, `c`.`Cliente` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cobranza_analitico`
--
DROP TABLE IF EXISTS `v_cobranza_analitico`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cobranza_analitico`  AS SELECT `c`.`id` AS `id`, `c`.`IdEmpresa` AS `IdEmpresa`, `c`.`Cliente` AS `IdDestinatario`, `d`.`id_destinatario` AS `id_destinatario`, `d`.`Cve_Clte` AS `Cve_Clte`, `cli`.`id_cliente` AS `id_cliente`, `cli`.`RazonSocial` AS `RazonSocial`, `cli`.`RazonComercial` AS `RazonComercial`, `c`.`Documento` AS `Documento`, `c`.`TipoDoc` AS `TipoDoc`, `c`.`FolioInterno` AS `FolioInterno`, `c`.`Saldo` AS `Saldo`, `c`.`Status` AS `Status`, `c`.`RutaId` AS `RutaId`, `r`.`descripcion` AS `RutaDescripcion`, `c`.`UltPago` AS `UltPago`, `c`.`FechaReg` AS `FechaReg`, `c`.`FechaVence` AS `FechaVence`, `c`.`DiaO` AS `DiaO`, CASE WHEN `c`.`FechaVence` is null THEN NULL ELSE to_days(curdate()) - to_days(`c`.`FechaVence`) END AS `DiasAtraso`, CASE WHEN `c`.`Saldo` is null THEN 'SIN_DATOS' WHEN `c`.`Saldo` <= 0 THEN 'PAGADO' WHEN `c`.`FechaVence` is null THEN 'SIN_VENCIMIENTO' WHEN `c`.`FechaVence` >= curdate() THEN 'VIGENTE' ELSE 'VENCIDO' END AS `EstatusTexto`, CASE WHEN `c`.`FechaVence` is null OR `c`.`Saldo` <= 0 THEN 'SIN_RIESGO' WHEN to_days(curdate()) - to_days(`c`.`FechaVence`) <= 0 THEN '0-0' WHEN to_days(curdate()) - to_days(`c`.`FechaVence`) between 1 and 30 THEN '1-30' WHEN to_days(curdate()) - to_days(`c`.`FechaVence`) between 31 and 60 THEN '31-60' WHEN to_days(curdate()) - to_days(`c`.`FechaVence`) between 61 and 90 THEN '61-90' WHEN to_days(curdate()) - to_days(`c`.`FechaVence`) between 91 and 120 THEN '91-120' ELSE '121+' END AS `RangoAntiguedad`, CASE WHEN `c`.`FechaReg` is null THEN NULL ELSE date_format(`c`.`FechaReg`,'%Y-%m') END AS `PeriodoMes` FROM (((`cobranza` `c` left join `c_destinatarios` `d` on(`d`.`id_destinatario` = `c`.`Cliente`)) left join `c_cliente` `cli` on(`cli`.`Cve_Clte` = `d`.`Cve_Clte`)) left join `t_ruta` `r` on(`r`.`ID_Ruta` = `c`.`RutaId`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cobranza_resumen_empresa`
--
DROP TABLE IF EXISTS `v_cobranza_resumen_empresa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cobranza_resumen_empresa`  AS SELECT `c`.`IdEmpresa` AS `IdEmpresa`, count(0) AS `Documentos`, sum(coalesce(`c`.`Saldo`,0)) AS `TotalSaldo`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and `c`.`FechaVence` < curdate() then `c`.`Saldo` else 0 end) AS `CarteraVencida`, sum(case when `c`.`Saldo` > 0 and `c`.`FechaVence` is not null and `c`.`FechaVence` >= curdate() then `c`.`Saldo` else 0 end) AS `CarteraVigente` FROM `cobranza` AS `c` GROUP BY `c`.`IdEmpresa` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_kardex_doble_partida`
--
DROP TABLE IF EXISTS `v_kardex_doble_partida`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `v_kardex_doble_partida`  AS SELECT cast(`tc`.`id` as unsigned) AS `tx_id`, `tc`.`fecha` AS `fecha_hora`, CASE WHEN ucase(`tm`.`nombre`) = 'ENTRADA' THEN 'ENTRADA' WHEN ucase(`tm`.`nombre`) in ('BAJA','SALIDA') THEN 'SALIDA' WHEN ucase(`tm`.`nombre`) in ('ACOMODO','TRANSFERENCIA') THEN 'TRANSFERENCIA' WHEN ucase(`tm`.`nombre`) like 'AJUSTE%' THEN 'AJUSTE' ELSE ucase(coalesce(`tm`.`nombre`,'DESCONOCIDO')) END AS `tipo_tx`, `tc`.`cve_articulo` AS `producto_id`, `art`.`des_articulo` AS `producto_nombre`, nullif(`tc`.`cve_lote`,'') AS `lote`, CASE WHEN ucase(`tm`.`nombre`) in ('SALIDA','BAJA') THEN abs(coalesce(`tc`.`cantidad`,0)) WHEN ucase(`tm`.`nombre`) like 'AJUSTE%' AND coalesce(`tc`.`cantidad`,0) < 0 THEN abs(`tc`.`cantidad`) WHEN ucase(`tm`.`nombre`) in ('TRANSFERENCIA','ACOMODO') THEN abs(coalesce(`tc`.`cantidad`,0)) ELSE 0 END AS `mov_ori`, CASE WHEN ucase(`tm`.`nombre`) = 'ENTRADA' THEN abs(coalesce(`tc`.`cantidad`,0)) WHEN ucase(`tm`.`nombre`) like 'AJUSTE%' AND coalesce(`tc`.`cantidad`,0) > 0 THEN `tc`.`cantidad` WHEN ucase(`tm`.`nombre`) in ('TRANSFERENCIA','ACOMODO') THEN abs(coalesce(`tc`.`cantidad`,0)) ELSE 0 END AS `mov_dst`, `tc`.`Cve_Almac` AS `alm_codigo`, `ca`.`des_almac` AS `zona_nombre`, `cap`.`nombre` AS `alm_nombre`, `cap`.`clave` AS `alm_clave`, coalesce(`cap`.`empresa_id`,`ca`.`empresa_id`,0) AS `empresa_id`, `cc`.`clave_empresa` AS `empresa_clave`, `cc`.`des_cia` AS `empresa_nombre`, `cc`.`des_rfc` AS `empresa_rfc`, `pr`.`Id` AS `proyecto_id`, `pr`.`Cve_Proyecto` AS `proyecto_clave`, `pr`.`Des_Proyecto` AS `proyecto_nombre`, `tc`.`Referencia` AS `Referencia`, `tc`.`cve_usuario` AS `cve_usuario`, `tc`.`origen` AS `origen`, `tc`.`destino` AS `destino`, `tc`.`stockinicial` AS `stockinicial`, `tc`.`ajuste` AS `ajuste`, `tc`.`id_TipoMovimiento` AS `id_TipoMovimiento`, `tc`.`Activo` AS `Activo`, `tc`.`Fec_Ingreso` AS `Fec_Ingreso`, `tc`.`Id_Motivo` AS `Id_Motivo` FROM ((((((`t_cardex` `tc` left join `t_tipomovimiento` `tm` on(`tm`.`id_TipoMovimiento` = `tc`.`id_TipoMovimiento`)) left join `c_almacen` `ca` on(convert(`ca`.`cve_almac` using utf8mb4) = convert(`tc`.`Cve_Almac` using utf8mb4))) left join `c_almacenp` `cap` on(convert(`cap`.`id` using utf8mb4) = convert(`tc`.`Cve_Almac` using utf8mb4) or convert(`cap`.`id` using utf8mb4) = convert(`ca`.`cve_almacenp` using utf8mb4))) left join `c_compania` `cc` on(`cc`.`empresa_id` = coalesce(`cap`.`empresa_id`,`ca`.`empresa_id`))) left join `c_articulo` `art` on(convert(`art`.`cve_articulo` using utf8mb4) = convert(`tc`.`cve_articulo` using utf8mb4) and `art`.`empresa_id` = coalesce(`cap`.`empresa_id`,`ca`.`empresa_id`))) left join `c_proyecto` `pr` on((convert(`pr`.`id_almacen` using utf8mb4) = convert(`cap`.`id` using utf8mb4) or convert(`pr`.`id_almacen` using utf8mb4) = convert(`ca`.`cve_almacenp` using utf8mb4) or convert(`pr`.`id_almacen` using utf8mb4) = convert(`tc`.`Cve_Almac` using utf8mb4)) and `pr`.`empresa_id` = coalesce(`cap`.`empresa_id`,`ca`.`empresa_id`))) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_kardex_enriquecido`
--
DROP TABLE IF EXISTS `v_kardex_enriquecido`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_kardex_enriquecido`  AS SELECT `k`.`id` AS `id`, `k`.`fecha` AS `fecha_ts`, cast(`k`.`fecha` as date) AS `fecha`, cast(`k`.`fecha` as time) AS `hora`, `tm`.`nombre` AS `tipo_mov_txt`, `k`.`cve_articulo` AS `cve_articulo`, ifnull(`a`.`des_articulo`,'') AS `desc_articulo`, `k`.`Cve_Almac` AS `cve_almac`, ifnull(`ap`.`des_almac`,`k`.`Cve_Almac`) AS `des_almac`, ifnull(`ap`.`zona`,'') AS `zona`, `k`.`cve_usuario` AS `id_usuario`, ifnull(`u`.`nombre`,`k`.`cve_usuario`) AS `operador`, `k`.`cantidad` AS `cantidad`, `k`.`cve_lote` AS `lote`, CASE WHEN ucase(`tm`.`nombre`) like 'ENTRADA%' THEN 1 WHEN ucase(`tm`.`nombre`) like 'SALIDA%' THEN -1 WHEN ucase(`tm`.`nombre`) like 'AJUSTE%' THEN 0 WHEN ucase(`tm`.`nombre`) like 'TRASPASO%' THEN 0 ELSE 0 END AS `signo` FROM ((((`t_cardex` `k` left join `t_tipomovimiento` `tm` on(cast(`tm`.`id_TipoMovimiento` as unsigned) = `k`.`id_TipoMovimiento`)) left join `c_articulo` `a` on(md5(`a`.`cve_articulo`) = md5(`k`.`cve_articulo`))) left join `v_almacen_compat` `ap` on(md5(`ap`.`clave`) = md5(`k`.`Cve_Almac`))) left join `v_usuario_compat` `u` on(md5(`u`.`cve_usuario`) = md5(`k`.`cve_usuario`))) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_kardex_enriquecido_v2`
--
DROP TABLE IF EXISTS `v_kardex_enriquecido_v2`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_kardex_enriquecido_v2`  AS SELECT `v`.`id` AS `id`, `v`.`fecha_ts` AS `fecha_ts`, `v`.`fecha` AS `fecha`, `v`.`hora` AS `hora`, `v`.`tipo_mov_txt` AS `tipo_mov_txt`, `v`.`cve_articulo` AS `cve_articulo`, `v`.`desc_articulo` AS `desc_articulo`, `v`.`cve_almac` AS `cve_almac`, `v`.`des_almac` AS `des_almac`, `v`.`zona` AS `zona`, `v`.`id_usuario` AS `id_usuario`, `v`.`operador` AS `operador`, `v`.`cantidad` AS `cantidad`, `v`.`lote` AS `lote`, `v`.`signo` AS `signo`, cast(`lp`.`cve_proyecto` as char charset utf8mb4) AS `cve_proyecto`, cast(`lp`.`cve_proveedor` as char charset utf8mb4) AS `cve_proveedor`, cast(`pr`.`des_proyecto` as char charset utf8mb4) AS `des_proyecto`, cast(`pv`.`des_proveedor` as char charset utf8mb4) AS `des_proveedor` FROM (((`v_kardex_enriquecido` `v` left join `v_bi_lote_proyecto` `lp` on(cast(`lp`.`cve_articulo` as char charset utf8mb4) = cast(`v`.`cve_articulo` as char charset utf8mb4) and cast(`lp`.`lote` as char charset utf8mb4) = cast(`v`.`lote` as char charset utf8mb4))) left join `v_bi_proyecto` `pr` on(`pr`.`cve_proyecto` = `lp`.`cve_proyecto`)) left join `v_bi_proveedor` `pv` on(`pv`.`cve_proveedor` = `lp`.`cve_proveedor`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_log_operaciones`
--
DROP TABLE IF EXISTS `v_log_operaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_log_operaciones`  AS SELECT `t_log_operaciones`.`empresa_id` AS `empresa_id`, trim(`t_log_operaciones`.`modulo`) AS `modulo`, trim(`t_log_operaciones`.`usuario`) AS `usuario`, trim(`t_log_operaciones`.`operacion`) AS `operacion`, trim(`t_log_operaciones`.`dispositivo`) AS `dispositivo`, trim(`t_log_operaciones`.`observaciones`) AS `observaciones`, coalesce(str_to_date(trim(`t_log_operaciones`.`fecha`),'%Y-%m-%d %H:%i:%s'),str_to_date(trim(`t_log_operaciones`.`fecha`),'%d/%m/%Y %H:%i:%s'),str_to_date(trim(`t_log_operaciones`.`fecha`),'%Y-%m-%d'),str_to_date(trim(`t_log_operaciones`.`fecha`),'%d/%m/%Y')) AS `fecha_dt`, trim(`t_log_operaciones`.`fecha`) AS `fecha_raw` FROM `t_log_operaciones` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_productividad_kardex_almacen`
--
DROP TABLE IF EXISTS `v_productividad_kardex_almacen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productividad_kardex_almacen`  AS SELECT `v`.`cve_almac` AS `cve_almac`, `v`.`des_almac` AS `des_almac`, count(0) AS `total_mov`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'ENTRADA%' then 1 else 0 end) AS `entradas`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'SALIDA%' then 1 else 0 end) AS `salidas`, sum(`v`.`signo` * `v`.`cantidad`) AS `balance_neto` FROM `v_kardex_enriquecido` AS `v` GROUP BY `v`.`cve_almac`, `v`.`des_almac` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_productividad_kardex_daily`
--
DROP TABLE IF EXISTS `v_productividad_kardex_daily`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productividad_kardex_daily`  AS SELECT `v`.`fecha` AS `fecha`, count(0) AS `total_mov`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'ENTRADA%' then 1 else 0 end) AS `entradas`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'SALIDA%' then 1 else 0 end) AS `salidas`, sum(`v`.`signo` * `v`.`cantidad`) AS `balance_neto` FROM `v_kardex_enriquecido` AS `v` GROUP BY `v`.`fecha` ORDER BY `v`.`fecha` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_productividad_kardex_topprod`
--
DROP TABLE IF EXISTS `v_productividad_kardex_topprod`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productividad_kardex_topprod`  AS SELECT `v`.`cve_articulo` AS `cve_articulo`, `v`.`desc_articulo` AS `desc_articulo`, count(0) AS `movimientos`, sum(case when `v`.`signo` = 1 then `v`.`cantidad` else 0 end) AS `total_entrada`, sum(case when `v`.`signo` = -1 then `v`.`cantidad` else 0 end) AS `total_salida` FROM `v_kardex_enriquecido` AS `v` GROUP BY `v`.`cve_articulo`, `v`.`desc_articulo` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_productividad_kardex_usuario`
--
DROP TABLE IF EXISTS `v_productividad_kardex_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productividad_kardex_usuario`  AS SELECT `v`.`id_usuario` AS `id_usuario`, `v`.`operador` AS `operador`, count(0) AS `total_mov`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'ENTRADA%' then 1 else 0 end) AS `entradas`, sum(case when ucase(`v`.`tipo_mov_txt`) like 'SALIDA%' then 1 else 0 end) AS `salidas`, round(count(0) / nullif(count(distinct `v`.`fecha`),0),2) AS `prom_mov_por_dia` FROM `v_kardex_enriquecido` AS `v` GROUP BY `v`.`id_usuario`, `v`.`operador` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_stg_bom_actual`
--
DROP TABLE IF EXISTS `v_stg_bom_actual`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_stg_bom_actual`  AS SELECT `a`.`empresa_id` AS `empresa_id`, `a`.`Cve_Articulo` AS `Cve_Articulo`, `art`.`des_articulo` AS `DescripcionPadre`, `a`.`Cve_ArtComponente` AS `Cve_ArtComponente`, `comp`.`des_articulo` AS `DescripcionComponente`, `a`.`Cantidad` AS `Cantidad`, `a`.`cve_umed` AS `cve_umed`, `a`.`Etapa` AS `Etapa` FROM ((`stg_t_artcompuesto` `a` join `stg_c_articulo` `art` on(`a`.`Cve_Articulo` = `art`.`cve_articulo` and `a`.`empresa_id` = `art`.`empresa_id`)) join `stg_c_articulo` `comp` on(`a`.`Cve_ArtComponente` = `comp`.`cve_articulo` and `a`.`empresa_id` = `comp`.`empresa_id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuario_compat`
--
DROP TABLE IF EXISTS `v_usuario_compat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_usuario_compat`  AS SELECT cast(`c_usuario`.`id_user` as signed) AS `id_usuario`, coalesce(`c_usuario`.`nombre_completo`,`c_usuario`.`des_usuario`,`c_usuario`.`cve_usuario`) AS `nombre`, `c_usuario`.`cve_usuario` AS `cve_usuario`, `c_usuario`.`email` AS `email`, `c_usuario`.`perfil` AS `perfil`, `c_usuario`.`Activo` AS `Activo` FROM `c_usuario` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `etl_connections`
--
ALTER TABLE `etl_connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alias` (`alias`);

--
-- Indices de la tabla `etl_object_meta`
--
ALTER TABLE `etl_object_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_meta` (`alias`,`remote_db`,`object_name`);

--
-- Indices de la tabla `etl_processes`
--
ALTER TABLE `etl_processes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `etl_process_docs`
--
ALTER TABLE `etl_process_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_proc_docs_process` (`process_id`);

--
-- Indices de la tabla `etl_process_objects`
--
ALTER TABLE `etl_process_objects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_proc_obj` (`process_id`,`alias`,`remote_db`,`object_name`);

--
-- Indices de la tabla `lc_diaso`
--
ALTER TABLE `lc_diaso`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `lc_folios`
--
ALTER TABLE `lc_folios`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `lc_relclilis`
--
ALTER TABLE `lc_relclilis`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `lc_rel_dest_ruta`
--
ALTER TABLE `lc_rel_dest_ruta`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `lc_v_vendedoresruta`
--
ALTER TABLE `lc_v_vendedoresruta`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `stg_c_charolas`
--
ALTER TABLE `stg_c_charolas`
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `ix_charolas_emp_alm` (`empresa_id`,`cve_almac`(80));

--
-- Indices de la tabla `td_entalmacen`
--
ALTER TABLE `td_entalmacen`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `th_entalmacen`
--
ALTER TABLE `th_entalmacen`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_license`
--
ALTER TABLE `t_license`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_log_operaciones`
--
ALTER TABLE `t_log_operaciones`
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_fecha_dt` (`fecha_dt`),
  ADD KEY `idx_usuario` (`usuario`(60)),
  ADD KEY `idx_modulo` (`modulo`(60)),
  ADD KEY `idx_operacion` (`operacion`(40));

--
-- Indices de la tabla `t_movcharolas`
--
ALTER TABLE `t_movcharolas`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_perfilesusuarios`
--
ALTER TABLE `t_perfilesusuarios`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_roles`
--
ALTER TABLE `t_roles`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_tipomovimiento`
--
ALTER TABLE `t_tipomovimiento`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `t_usu_alm_pre`
--
ALTER TABLE `t_usu_alm_pre`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `v_existencia_proyecto`
--
ALTER TABLE `v_existencia_proyecto`
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `etl_connections`
--
ALTER TABLE `etl_connections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etl_object_meta`
--
ALTER TABLE `etl_object_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etl_processes`
--
ALTER TABLE `etl_processes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etl_process_docs`
--
ALTER TABLE `etl_process_docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etl_process_objects`
--
ALTER TABLE `etl_process_objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `etl_process_docs`
--
ALTER TABLE `etl_process_docs`
  ADD CONSTRAINT `fk_proc_docs_process` FOREIGN KEY (`process_id`) REFERENCES `etl_processes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `etl_process_objects`
--
ALTER TABLE `etl_process_objects`
  ADD CONSTRAINT `fk_proc_obj_process` FOREIGN KEY (`process_id`) REFERENCES `etl_processes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
