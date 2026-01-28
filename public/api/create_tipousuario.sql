-- Create c_tipousuario table
CREATE TABLE IF NOT EXISTS `c_tipousuario` (
  `id_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `des_tipo` varchar(50) NOT NULL,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default values if they don't exist
INSERT INTO `c_tipousuario` (`id_tipo`, `des_tipo`) VALUES
(1, 'Administrador'),
(2, 'Usuario'),
(3, 'Consulta')
ON DUPLICATE KEY UPDATE des_tipo = VALUES(des_tipo);
