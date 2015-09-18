-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--
-- Host: mysql.pokemonshowdown.com
-- Generation Time: Jan 23, 2013 at 04:24 PM
-- Server version: 5.1.39
-- PHP Version: 5.3.13

--
-- Database: pokemonshowdown
--

-- --------------------------------------------------------

--
-- Table structure for table ntbb_ladder
--

CREATE TABLE IF NOT EXISTS ntbb_ladder (
  entryid serial NOT NULL PRIMARY KEY,
  serverid text NOT NULL,
  formatid text NOT NULL,
  userid text NOT NULL,
  username text NOT NULL,
  w int NOT NULL DEFAULT '0',
  l int NOT NULL DEFAULT '0',
  t int NOT NULL DEFAULT '0',
  gxe double precision NOT NULL DEFAULT '0',
  r double precision NOT NULL DEFAULT '1500',
  rd double precision NOT NULL DEFAULT '350',
  sigma double precision NOT NULL DEFAULT '0.06',
  rptime int8 NOT NULL,
  rpr double precision NOT NULL DEFAULT '1500',
  rprd double precision NOT NULL DEFAULT '350',
  rpsigma double precision NOT NULL DEFAULT '0.06',
  rpdata text NOT NULL,
  acre double precision NOT NULL DEFAULT '1000',
  lacre double precision NOT NULL
);
CREATE INDEX ON ntbb_ladder (formatid,userid,gxe);
CREATE INDEX ON ntbb_ladder (serverid);
CREATE INDEX ON ntbb_ladder (acre);
CREATE INDEX ON ntbb_ladder (lacre);
CREATE INDEX ON ntbb_ladder (userid);
CREATE INDEX ON ntbb_ladder (formatid);
