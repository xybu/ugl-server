CREATE USER 'ugl_user'@'localhost' IDENTIFIED BY 'I6BjdVMi6a';
CREATE DATABASE  IF NOT EXISTS `ugl_test` /*!40100 DEFAULT CHARACTER SET latin1 */;
GRANT ALL PRIVILEGES ON ugl_test.* TO 'ugl_user'@'localhost';