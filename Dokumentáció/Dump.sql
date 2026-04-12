-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: mysql.omega:3306
-- Létrehozás ideje: 2026. Ápr 12. 10:18
-- Kiszolgáló verziója: 5.7.42-log
-- PHP verzió: 7.2.34-61+0~20260213.113+debian12~1.gbp7055a0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `mozivizsga26`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `director` varchar(100) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `release_year` year(4) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `poster_url` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- A tábla adatainak kiíratása `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `director`, `duration`, `release_year`, `genre`, `rating`, `poster_url`, `trailer_url`, `active`) VALUES
(1, 'Inception', 'A bűnöző, aki kódokat lop álmokból, megkapja a lehetőséget, hogy tisztuljon múltja bűneitől, ha beültet egy gondolatot egy másik elméjébe.', 'Christopher Nolan', 148, '2010', 'Sci-Fi, Thriller', 8.8, 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(2, 'A remény rabjai', 'Két bebörtönzött férfi köt barátságot évek során, megtalálva vigaszt és végül megváltást az egyszerű cselekedetek révén.', 'Frank Darabont', 142, '1994', 'Dráma', 9.3, 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(3, 'A sötét lovag', 'Amikor a Joker szándékosan káoszba taszítja Gotham Cityt, Batmannek meg kell szembenéznie az egyik legnagyobb szellemi és fizikai kihívással.', 'Christopher Nolan', 152, '2008', 'Akció, Krimi, Dráma', 9.0, 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(4, 'Ponyvaregény', 'A drogkereskedő, a feleségének titkos megbízása, a boxoló és két bandatag életének összekapcsolódása Los Angelesben.', 'Quentin Tarantino', 154, '1994', 'Krimi, Dráma', 8.9, 'https://m.media-amazon.com/images/M/MV5BNGNhMDIzZTUtNTBlZi00MTRlLWFjM2ItYzViMjE3YzI5MjljXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(5, 'Forrest Gump', 'A közepes IQ-jú Alabamai férfi elképesztő utazásra indul, hogy újra egyesítse szerelmével, miközben befolyásolja a történelmi eseményeket útközben.', 'Robert Zemeckis', 142, '1994', 'Dráma, Romantikus', 8.8, 'https://m.media-amazon.com/images/M/MV5BNWIwODRlZTUtY2U3ZS00Yzg1LWJhNzYtMmZiYmEyNmU1NjMzXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(6, 'A keresztapa', 'A Vito Corleone vezette maffiacsalád fejének lánya esküvőjén történetek és titkok szövődnek, miközben a család a hatalom megtartásáért küzd a szervezett bűnözés világában.', 'Francis Ford Coppola', 175, '1972', 'Krimi, Dráma', 9.2, 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(7, 'Csillagok között', 'Egy volt NASA-pilóta és kutatócsoportja egy féreglyukon át utazva próbálja megtalálni az emberiség új otthonát, miközben az idő relativitása és az űr magánya próbára teszi emberségüket.', 'Christopher Nolan', 169, '2014', 'Sci-Fi, Kaland, Dráma', 8.7, 'https://m.media-amazon.com/images/M/MV5BZjdkOTU3MDktN2IxOS00OGEyLWFmMjktY2FiMmZkNWIyODZiXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(8, 'Mátrix', 'Egy programozó felfedezi, hogy a világ, amelyben élünk, csak egy illúzió, és egy titokzatos lázadó csoport segítségével próbálja megszabadítani az emberiséget a gépek uralmától.', 'Lana Wachowski, Lilly Wachowski', 136, '1999', 'Sci-Fi, Akció', 8.7, 'https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(9, 'Nagymenők', 'Egy fiatal férfi története, aki felküzdötte magát a maffia ranglétráján, de a pénz, a hatalom és a paranoia végül majdnem mindenét felemészti.', 'Martin Scorsese', 146, '1990', 'Életrajzi, Krimi, Dráma', 8.7, 'https://m.media-amazon.com/images/M/MV5BY2NkZjEzMDgtN2RjYy00YzM1LWI4ZmQtMjIwYjFjNmI3ZGEwXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(10, 'Harcosok klubja', 'Egy álmatlanságban szenvedő irodai dolgozó és egy rejtélyes szappangyártó földalatti harcművészeti klubot alapítanak, ami forradalommá növi ki magát.', 'David Fincher', 139, '1999', 'Dráma', 8.8, 'https://m.media-amazon.com/images/M/MV5BMmEzNTkxYjQtZTc0MC00YTVjLTg5ZTEtZWMwOWVlYzY0NWIwXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(11, 'A Gyűrűk Ura: A Gyűrű Szövetsége', 'Egy békés hobbit, Frodó Zsákos megtudja, hogy az ő feladata elpusztítani az Egy Gyűrűt, amelyet a Sötét Úr, Sauron akar megszerezni, hogy rabságba döntse Középföldét.', 'Peter Jackson', 178, '2001', 'Kaland, Fantasy, Akció', 8.8, 'https://m.media-amazon.com/images/M/MV5BN2EyZjM3NzUtNWUzMi00MTgxLWI0NTctMzY4M2VlOTdjZWRiXkEyXkFqcGdeQXVyNDUzOTQ5MjY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(12, 'Gladiátor', 'Egy becsületes római hadvezér árulás által elveszti családját és rangját, majd rabszolgából gladiátorrá válva áll bosszút a korrupt császár ellen.', 'Ridley Scott', 155, '2000', 'Akció, Kaland, Dráma', 8.5, 'https://m.media-amazon.com/images/M/MV5BMDliMmNhNDEtODUyOS00MjNlLTgxODEtN2U3NzIxMGVkZTA1L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(13, 'Schindler listája', 'Oskar Schindler német üzletember hatalmas vagyont szerez a második világháború alatt zsidó munkások kizsákmányolásával, majd életét kockáztatva megmenti több mint ezer alkalmazottját a haláltól.', 'Steven Spielberg', 195, '1993', 'Életrajzi, Dráma, Történelmi', 9.0, 'https://m.media-amazon.com/images/M/MV5BNDE4OTMxMTctNmRhYy00NWE2LTg3YzItYTk3M2UwOTU5Njg4XkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(14, 'Chihiro Szellemországa', 'Egy tízéves kislány, Chihiro szüleivel költözik, amikor betévednek egy szellemek lakta vidámparkba, ahol meg kell találnia a kiutat és visszaszereznie a valódi nevét.', 'Hayao Miyazaki', 125, '2001', 'Animáció, Kaland, Családi', 8.6, 'https://m.media-amazon.com/images/M/MV5BMjlmZmI5MDctNDE2YS00YWE0LWE5ZWItZDBhYWQ0NTcxNWRhXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(15, 'A halálsoron eltöltött mérföld', 'Egy halálsoron dolgozó börtönőr találkozik egy különleges képességekkel rendelkező, gyilkosságért elítélt óriással, ami örökre megváltoztatja az életét.', 'Frank Darabont', 189, '1999', 'Krimi, Dráma, Fantasy', 8.6, 'https://m.media-amazon.com/images/M/MV5BMTUxMzQyNjA5MF5BMl5BanBnXkFtZTYwOTU2NTY3._V1_FMjpg_UX1000_.jpg', NULL, 1),
(16, 'A Gyűrűk Ura: A két torony', 'Frodó és Sam folytatják veszélyes útjukat Mordor felé, miközben Aragorn, Legolas és Gimli a megosztott embereket próbálják egyesíteni Szarumán seregei ellen.', 'Peter Jackson', 179, '2002', 'Kaland, Fantasy, Akció', 8.7, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjetbuOHVUTAR1V0G2uO8g4ZSfDoKwi_wK2f3WKY_dccA_QatJiPDfOhooSkbQRhdjAgMek-NCV3xzwRfdrnWcG6Q9vK_YmXpbjo0SXMCgeomrkW8A&s=10&ec=121528417', '', 1),
(17, 'A Gyűrűk Ura: A király visszatér', 'A végső összecsapás Középföldéért: Aragorn trónra lép, miközben Frodó és Sam eléri a Végzet-hegyét, hogy megsemmisítsék az Egy Gyűrűt.', 'Peter Jackson', 201, '2003', 'Kaland, Fantasy, Akció', 8.9, 'https://m.media-amazon.com/images/M/MV5BNzA5ZDNlZWMtM2NhNS00NDJjLTk4NDItYTRmY2EwMWZlMTY3XkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(18, 'Csillagok háborúja IV: Egy új remény', 'Egy fiatal farmfiú, Luke Skywalker egy öreg jedi lovaggal és két szélhámossal indul, hogy megmentse a lázadók hercegnőjét és elpusztítsa a Birodalom félelmetes űrállomását.', 'George Lucas', 121, '1977', 'Sci-Fi, Akció, Kaland', 8.6, 'https://m.media-amazon.com/images/M/MV5BOTA5NjhiOTAtZWM0ZC00MWNhLThiMzEtZDFkOTk2OTU1ZDJkXkEyXkFqcGdeQXVyMTA4NDI1NTQx._V1_FMjpg_UX1000_.jpg', NULL, 1),
(19, 'Csillagok háborúja V: A Birodalom visszavág', 'A Birodalom visszavág: a lázadók menekülnek, Luke Yodától tanulja az Erő útjait, miközben Darth Vader könyörtelenül üldözi őket.', 'Irvin Kershner', 124, '1980', 'Sci-Fi, Akció, Kaland', 8.7, 'https://m.media-amazon.com/images/M/MV5BYmU1NDRjNDgtMzhiMi00NjZmLTg5NGItZDNiZjU5NTU4OTE0XkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(20, 'Csillagok háborúja VI: A jedi visszatér', 'A végső csata: Luke szembeszáll Darth Vaderrel és a császárral, miközben a lázadók a Halálcsillag második változatát támadják meg.', 'Richard Marquand', 131, '1983', 'Sci-Fi, Akció, Kaland', 8.3, 'https://m.media-amazon.com/images/M/MV5BOWZlMjFiYzgtMTUzNC00Y2IzLTk1NTMtZmNhMTczNTk0ODk1XkEyXkFqcGdeQXVyNTAyODkwOQ@@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(21, 'A bárányok hallgatnak', 'Egy fiatal FBI-gyakornok, Clarice Starling egy bebörtönzött kannibál sorozatgyilkos, Dr. Hannibal Lecter segítségét kéri, hogy elkapjon egy másik aktív sorozatgyilkost.', 'Jonathan Demme', 118, '1991', 'Krimi, Dráma, Thriller', 8.6, 'https://m.media-amazon.com/images/M/MV5BNjNhZTk0ZmEtNjJhMi00YzFlLWE1MmEtYzM1M2ZmMGMwMTU4XkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(22, 'Ryan közlegény megmentése', 'A második világháborúban egy csapat katonát küldenek, hogy megmentsenek egy ejtőernyőst, akinek három testvére meghalt a harcokban.', 'Steven Spielberg', 169, '1998', 'Dráma, Háborús', 8.6, 'https://m.media-amazon.com/images/M/MV5BZjhkMDM4MWItZTVjOC00ZDRhLThmYTAtM2I5NzBmNmNlMzI1XkEyXkFqcGdeQXVyNDYyMDk5MTU@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(23, 'Hét', 'Két nyomozó, a tapasztalt Somerset és az újonc Mills egy sorozatgyilkos után nyomoz, aki a hét főbűn alapján öl.', 'David Fincher', 127, '1995', 'Krimi, Dráma, Rejtély', 8.6, 'https://th.bing.com/th/id/OIP.AtFITbVkj583yxCL_bF3IAHaLH?w=115&h=180&c=7&r=0&o=7&pid=1.7&rm=3', NULL, 1),
(24, 'A szokásos gyanúsítottak', 'Egy túlélő meséli el a rendőrségen, hogyan került egy bűnözőkből álló csoport egy titokzatos bűnvezér, Keyser Söze csapdájába.', 'Bryan Singer', 106, '1995', 'Krimi, Rejtély, Thriller', 8.5, 'https://m.media-amazon.com/images/M/MV5BYTViNjMyNmUtNDFkNC00ZDRlLThmMDUtZDU2YWE4NGI2ZjVmXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(25, 'Léon, a profi', 'Egy profi bérgyilkos befogad egy tizenkét éves lányt, akinek családját megölték, és megtanítja neki a szakmát.', 'Luc Besson', 110, '1994', 'Akció, Krimi, Dráma', 8.5, 'https://th.bing.com/th/id/OIP.TDMweJET7BD1ntNm3M4nogHaJ4?w=127&h=180&c=7&r=0&o=7&pid=1.7&rm=3', NULL, 1),
(26, 'A tégla', 'Egy rendőrségi beépített ember és egy maffia besúgó próbálják leleplezni egymást Boston alvilágában.', 'Martin Scorsese', 151, '2006', 'Krimi, Dráma, Thriller', 8.5, 'https://m.media-amazon.com/images/M/MV5BMTI1MTY2OTIxNV5BMl5BanBnXkFtZTYwNjQ4NjY3._V1_FMjpg_UX1000_.jpg', NULL, 1),
(27, 'Whiplash', 'Egy ambiciózus dzsesszdobos és a kegyetlen tanára közötti kapcsolat története, ahol a tehetség és a tökéletesség iránti vágy összecsap.', 'Damien Chazelle', 106, '2014', 'Dráma, Zenés', 8.5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSxSWu-m7h79QdP9lchQXIPm_aakX3K0het8yriikbpYZnEj8VwjLMKjSIvpexMmZvhHvE7G9CvqIriovOuLc4rUNANTro502bs-rOHKw2OwcSNacg&s&ec=121528417', NULL, 1),
(28, 'A tökéletes trükk', 'Két rivalizáló bűvész a viktoriánus Londonban megszállottan kutatja a tökéletes trükk titkát, ami végül mindkettejük vesztét okozza.', 'Christopher Nolan', 130, '2006', 'Dráma, Rejtély, Sci-Fi', 8.5, 'https://m.media-amazon.com/images/M/MV5BMjA4NDI0MTIxNF5BMl5BanBnXkFtZTYwNTM0MzY2._V1_FMjpg_UX1000_.jpg', NULL, 1),
(29, 'Élősködők', 'Egy szegény család lassan beszivárog egy gazdag család életébe, ami váratlan és borzalmas következményekkel jár.', 'Bong Joon-ho', 132, '2019', 'Dráma, Thriller', 8.6, 'https://m.media-amazon.com/images/M/MV5BYWZjMjk3ZTItODQ2ZC00NTY5LWE0ZDYtZTI3MjcwN2Q5NTVkXkEyXkFqcGdeQXVyODk4OTc3MTY@._V1_FMjpg_UX1000_.jpg', NULL, 1),
(30, 'Az oroszlánkirály', 'Egy fiatal oroszlán, Simba száműzetésbe kényszerül, de vissza kell térnie, hogy elfoglalja méltó helyét a körforgásban.', 'Roger Allers, Rob Minkoff', 88, '1994', 'Animáció, Kaland, Dráma', 8.5, 'https://m.media-amazon.com/images/M/MV5BYTYxNGMyZTYtMjE3MS00MzNjLWFjNmYtMDk3N2FmM2JiM2M1XkEyXkFqcGdeQXVyNjY5NDU4NzI@._V1_FMjpg_UX1000_.jpg', NULL, 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `screening_id` int(11) NOT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `currency` varchar(3) DEFAULT 'HUF',
  `status` varchar(50) DEFAULT 'pending',
  `seats` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- A tábla adatainak kiíratása `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `screening_id`, `stripe_session_id`, `stripe_payment_intent`, `amount`, `currency`, `status`, `seats`, `created_at`, `paid_at`) VALUES
(1, 1, 10, 'cs_test_a1PkGe4U5gozvj4iGQpXN8ZbMcu7OecEBtsbb5l5d3ZBnf2oCpSnIOagRq', NULL, 2200, 'HUF', 'pending', '[\"F08\"]', '2026-02-26 10:29:59', NULL),
(2, 1, 10, 'cs_test_a1BqSg5hXovciw8qJeo3xoGnL2B2W7yBzS8445CUtea2FcGxTK7QyKHnkP', NULL, 2200, 'HUF', 'pending', '[\"H08\"]', '2026-02-26 10:31:33', NULL),
(3, 1, 10, 'cs_test_a1RfHF9scjAmyIcidAgtdB22AFPDpcKGxTaRPqxAXi46HFOsWuUb7Lmt4I', 'pi_3T51sPK3NyMFNVw41d8w1VtA', 2200, 'HUF', 'paid', '[\"H08\"]', '2026-02-26 10:32:43', '2026-02-26 10:33:03'),
(4, 4, 11, 'cs_test_a1BvPwhomEM6340bQvFssUs7iAY2J8w7UySRcY4EVNNSO6TpBWsRL4mb2E', 'pi_3T51ugK3NyMFNVw41wYW4WDC', 6600, 'HUF', 'paid', '[\"E07\",\"E08\",\"E09\"]', '2026-02-26 10:34:36', '2026-02-26 10:35:25'),
(5, 4, 15, 'cs_test_a1n7zyK3FLK3g4laUG9aTmUROcPDeuTgWkEzcyKpLsN8nhRijO8sHPO4do', NULL, 2200, 'HUF', 'pending', '[\"G04\"]', '2026-02-27 10:35:20', NULL),
(6, 4, 15, 'cs_test_a1tSv4LCTmq4s2pYwhXz1EF3W4bky8ndSHoeH44p6ifV8WbyWmysWkAF8u', NULL, 2200, 'HUF', 'pending', '[\"G04\"]', '2026-02-27 10:35:21', NULL),
(7, 1, 28, 'cs_test_a1YgQXJKtXvpspJ9zlghHKOO3uYH224WbPcml22u5w1P8pexJxkqbA5eII', 'pi_3T6S10K3NyMFNVw403LCkKb8', 2200, 'HUF', 'paid', '[\"E08\"]', '2026-03-02 08:38:05', '2026-03-02 08:39:50'),
(8, 1, 28, 'cs_test_a1EYYY2Gdk4YPcbWNwAVp9yPIiUWRt2vaTphWAh2aqsRibdUYmKq3x3H4h', NULL, 2200, 'HUF', 'pending', '[\"D11\"]', '2026-03-02 08:41:41', NULL),
(9, 4, 28, 'cs_test_a1G1MpdPdUQcNkt95J8D7xmDmVtCXRzLqawKqHnzSS7L4NTrwS0LHf1971', NULL, 13200, 'HUF', 'pending', '[\"A01\",\"A02\",\"A03\",\"A04\",\"A05\",\"A06\"]', '2026-03-02 09:24:06', NULL),
(10, 4, 31, 'cs_test_a1oN3thGbYECx6Zplzj2oQrlOojbjsTP5x35CjkjfjHp447W1VencF2Zis', 'pi_3T6SkeK3NyMFNVw41JCSOIUk', 13200, 'HUF', 'paid', '[\"A01\",\"A02\",\"A03\",\"A04\",\"A05\",\"A06\"]', '2026-03-02 09:25:57', '2026-03-02 09:27:01'),
(11, 1, 37, 'cs_test_a1gOY1i3QbEd28TCRiRCUNTCrBvftOm4v6BezsbpPyiyxWth1q1mK5KWMZ', NULL, 2200, 'HUF', 'pending', '[\"E08\"]', '2026-03-04 07:42:19', NULL),
(12, 1, 37, 'cs_test_a1yaV9TNew2aRRg0AES0bnaDtkdnB9tQ57iTX7xHBmuIvYvyBtPPuh0cC5', 'pi_3T7A67K3NyMFNVw413dWNAqq', 2200, 'HUF', 'paid', '[\"E08\"]', '2026-03-04 07:42:21', '2026-03-04 07:44:03'),
(13, 5, 71, 'cs_test_a18pFDhDIBQkRoCmslewRNVVsfiRscftUQVE8m1mHALAewhCYKhRHPwG9Q', 'pi_3T8z6wK3NyMFNVw41cwkaOSD', 2200, 'HUF', 'paid', '[\"E08\"]', '2026-03-09 08:23:59', '2026-03-09 08:24:26'),
(14, 8, 114, 'cs_test_a1UE8HBEBYIUs3LFAqIBkNysjPgd8n3nmMxoSk97THFWFEEMeovG4alG8F', 'pi_3TCEXpK3NyMFNVw40ydOSJaB', 13200, 'HUF', 'paid', '[\"F05\",\"G04\",\"G06\",\"G07\",\"H05\",\"H06\"]', '2026-03-18 07:28:57', '2026-03-18 07:29:36'),
(15, 1, 132, 'cs_test_a1yV01x0jYtqRaZFLbX41pfGzj4B5vi5rI55yxbhCbW9tPWhq9haCGCu54', NULL, 2200, 'HUF', 'pending', '[\"F08\"]', '2026-03-22 16:46:21', NULL),
(16, 1, 132, 'cs_test_a1LPMNcTNS5XrYUxDSW4so1LJzpHDyP5K6Rbh9HodOHCBqV8bnQxT4QJJP', 'pi_3TDp96K3NyMFNVw40C3XJFEl', 2200, 'HUF', 'paid', '[\"F08\"]', '2026-03-22 16:46:22', '2026-03-22 16:46:49'),
(17, 1, 134, 'cs_test_a1n8410gXKCdzTQHke8rNl3jor4YxzxLidwFRz91kTftjznujMtnm1fE8p', 'pi_3TDpA6K3NyMFNVw411NmnjDu', 2200, 'HUF', 'paid', '[\"H08\"]', '2026-03-22 16:47:23', '2026-03-22 16:47:42'),
(18, 1, 135, 'CASH-135-1-1774198676-69c01f941070a', NULL, 2200, 'HUF', 'paid', '[\"H08\"]', '2026-03-22 16:57:56', NULL),
(19, 1, 134, 'CASH-134-1-1774200614-69c02726af81c', NULL, 2200, 'HUF', 'paid', '[\"H09\"]', '2026-03-22 17:30:14', NULL),
(20, 1, 134, 'cs_test_a1S7R5XOhpE3rAfSN3pRHnr1cV4zxEWrnOdFhwdL8U5XkoeDH4MDEe41vC', NULL, 2200, 'HUF', 'pending', '[\"H07\"]', '2026-03-22 17:30:25', NULL),
(21, 1, 137, 'CASH-137-1-1774202407-69c02e2714af0', NULL, 2200, 'HUF', 'paid', '[\"H09\"]', '2026-03-22 18:00:07', NULL),
(22, 5, 134, 'CASH-134-5-1774205705-69c03b0900275', NULL, 2200, 'HUF', 'paid', '[\"E08\"]', '2026-03-22 18:55:05', NULL),
(23, 1, 137, 'CASH-137-1-1774255711-69c0fe5fa5475', NULL, 2200, 'HUF', 'paid', '[\"H08\"]', '2026-03-23 08:48:31', NULL),
(24, 1, 146, 'CASH-146-1-1774259489-69c10d21ca060', NULL, 4400, 'HUF', 'paid', '[\"A08\",\"A09\"]', '2026-03-23 09:51:29', NULL),
(25, 1, 157, 'CASH-157-1-1774608302-69c65faec9bc2', NULL, 13200, 'HUF', 'paid', '[\"E05\",\"E06\",\"E07\",\"E10\",\"E11\",\"E12\"]', '2026-03-27 10:45:02', NULL),
(26, 1, 175, 'cs_test_a177KyBjBBgAYrQzSP7u1YhcxUQ3rKuxbg2gcqHQjGR3tguy8mi7GhWOL0', 'pi_3TGaLTK3NyMFNVw41NdWH4u4', 2200, 'HUF', 'paid', '[\"E08\"]', '2026-03-30 07:33:52', '2026-03-30 07:34:52');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `screenings`
--

CREATE TABLE `screenings` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `screening_date` date NOT NULL,
  `screening_time` time NOT NULL,
  `hall_number` int(11) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `available_seats` int(11) DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- A tábla adatainak kiíratása `screenings`
--

INSERT INTO `screenings` (`id`, `movie_id`, `screening_date`, `screening_time`, `hall_number`, `price`, `available_seats`) VALUES
(7, 7, '2026-02-26', '21:30:00', 3, 2200.00, 120),
(8, 14, '2026-02-26', '23:45:00', 3, 2200.00, 120),
(9, 1, '2026-02-26', '19:00:00', 3, 2200.00, 120),
(10, 20, '2026-02-26', '14:00:00', 3, 2200.00, 119),
(11, 22, '2026-02-26', '16:30:00', 1, 2200.00, 117),
(12, 22, '2026-02-27', '23:45:00', 3, 2200.00, 120),
(13, 2, '2026-02-27', '21:30:00', 2, 2200.00, 120),
(14, 21, '2026-02-27', '16:30:00', 5, 2200.00, 120),
(15, 1, '2026-02-27', '14:00:00', 4, 2200.00, 120),
(16, 14, '2026-02-27', '19:00:00', 4, 2200.00, 120),
(17, 11, '2026-02-28', '16:30:00', 1, 2200.00, 120),
(18, 15, '2026-02-28', '23:45:00', 2, 2200.00, 120),
(19, 30, '2026-02-28', '21:30:00', 1, 2200.00, 120),
(20, 19, '2026-02-28', '19:00:00', 2, 2200.00, 120),
(21, 13, '2026-02-28', '14:00:00', 1, 2200.00, 120),
(23, 11, '2026-03-01', '16:30:00', 5, 2200.00, 120),
(24, 19, '2026-03-01', '14:00:00', 3, 2200.00, 120),
(25, 16, '2026-03-01', '23:45:00', 2, 2200.00, 120),
(26, 29, '2026-03-01', '19:00:00', 4, 2200.00, 120),
(27, 30, '2026-03-02', '19:00:00', 2, 2200.00, 120),
(28, 5, '2026-03-02', '14:00:00', 1, 2200.00, 119),
(29, 20, '2026-03-02', '21:30:00', 3, 2200.00, 120),
(30, 29, '2026-03-02', '23:45:00', 1, 2200.00, 120),
(31, 10, '2026-03-02', '16:30:00', 2, 2200.00, 114),
(32, 9, '2026-03-03', '19:00:00', 5, 2200.00, 120),
(33, 14, '2026-03-03', '23:45:00', 5, 2200.00, 120),
(34, 16, '2026-03-03', '14:00:00', 3, 2200.00, 120),
(35, 26, '2026-03-03', '16:30:00', 2, 2200.00, 120),
(36, 12, '2026-03-03', '21:30:00', 1, 2200.00, 120),
(37, 12, '2026-03-04', '12:30:00', 1, 2200.00, 119),
(38, 26, '2026-03-04', '21:30:00', 1, 2200.00, 120),
(39, 17, '2026-03-04', '14:00:00', 1, 2200.00, 120),
(40, 9, '2026-03-04', '23:45:00', 2, 2200.00, 120),
(41, 14, '2026-03-04', '19:00:00', 1, 2200.00, 120),
(42, 9, '2026-03-05', '21:30:00', 5, 2200.00, 120),
(43, 26, '2026-03-05', '19:00:00', 5, 2200.00, 120),
(44, 5, '2026-03-05', '23:45:00', 1, 2200.00, 120),
(45, 4, '2026-03-05', '16:30:00', 4, 2200.00, 120),
(46, 21, '2026-03-05', '14:00:00', 3, 2200.00, 120),
(47, 26, '2026-03-06', '19:00:00', 5, 2200.00, 120),
(48, 13, '2026-03-06', '16:30:00', 2, 2200.00, 120),
(49, 20, '2026-03-06', '21:30:00', 3, 2200.00, 120),
(50, 14, '2026-03-06', '14:00:00', 1, 2200.00, 120),
(51, 12, '2026-03-06', '23:45:00', 1, 2200.00, 120),
(52, 17, '2026-03-07', '23:45:00', 5, 2200.00, 120),
(53, 3, '2026-03-07', '16:30:00', 4, 2200.00, 120),
(54, 18, '2026-03-07', '14:00:00', 5, 2200.00, 120),
(55, 29, '2026-03-07', '19:00:00', 1, 2200.00, 120),
(56, 6, '2026-03-07', '21:30:00', 1, 2200.00, 120),
(58, 18, '2026-03-08', '19:00:00', 1, 2200.00, 120),
(59, 7, '2026-03-08', '16:30:00', 2, 2200.00, 120),
(60, 15, '2026-03-08', '21:30:00', 5, 2200.00, 120),
(61, 21, '2026-03-08', '14:00:00', 1, 2200.00, 120),
(62, 16, '2026-03-08', '14:00:00', 4, 2200.00, 120),
(63, 13, '2026-03-08', '23:45:00', 4, 2200.00, 120),
(64, 26, '2026-03-08', '16:30:00', 4, 2200.00, 120),
(65, 24, '2026-03-08', '19:00:00', 2, 2200.00, 120),
(66, 6, '2026-03-08', '21:30:00', 3, 2200.00, 120),
(67, 8, '2026-03-09', '19:00:00', 4, 2200.00, 120),
(68, 18, '2026-03-09', '21:30:00', 1, 2200.00, 120),
(69, 19, '2026-03-09', '23:45:00', 2, 2200.00, 120),
(70, 4, '2026-03-09', '16:30:00', 4, 2200.00, 120),
(71, 10, '2026-03-09', '14:00:00', 2, 2200.00, 119),
(72, 20, '2026-03-10', '23:45:00', 1, 2200.00, 120),
(73, 22, '2026-03-10', '16:30:00', 4, 2200.00, 120),
(74, 3, '2026-03-10', '21:30:00', 4, 2200.00, 120),
(75, 25, '2026-03-10', '14:00:00', 4, 2200.00, 120),
(76, 17, '2026-03-10', '19:00:00', 2, 2200.00, 120),
(77, 12, '2026-03-11', '23:45:00', 4, 2200.00, 120),
(78, 14, '2026-03-11', '21:30:00', 4, 2200.00, 120),
(79, 4, '2026-03-11', '16:30:00', 1, 2200.00, 120),
(80, 27, '2026-03-11', '19:00:00', 1, 2200.00, 120),
(81, 3, '2026-03-11', '14:00:00', 3, 2200.00, 120),
(82, 20, '2026-03-12', '21:30:00', 3, 2200.00, 120),
(83, 7, '2026-03-12', '19:00:00', 1, 2200.00, 120),
(84, 2, '2026-03-12', '16:30:00', 1, 2200.00, 120),
(85, 28, '2026-03-12', '14:00:00', 4, 2200.00, 120),
(86, 5, '2026-03-12', '23:45:00', 1, 2200.00, 120),
(87, 9, '2026-03-13', '19:00:00', 2, 2200.00, 120),
(88, 17, '2026-03-13', '14:00:00', 2, 2200.00, 120),
(89, 15, '2026-03-13', '16:30:00', 4, 2200.00, 120),
(90, 14, '2026-03-13', '23:45:00', 5, 2200.00, 120),
(91, 29, '2026-03-13', '21:30:00', 3, 2200.00, 120),
(92, 22, '2026-03-14', '16:30:00', 5, 2200.00, 120),
(93, 3, '2026-03-14', '21:30:00', 2, 2200.00, 120),
(94, 5, '2026-03-14', '23:45:00', 1, 2200.00, 120),
(95, 10, '2026-03-14', '19:00:00', 3, 2200.00, 120),
(96, 9, '2026-03-14', '14:00:00', 3, 2200.00, 120),
(97, 13, '2026-03-15', '21:30:00', 2, 2200.00, 120),
(98, 9, '2026-03-15', '16:30:00', 5, 2200.00, 120),
(99, 10, '2026-03-15', '19:00:00', 3, 2200.00, 120),
(100, 26, '2026-03-15', '23:45:00', 1, 2200.00, 120),
(101, 27, '2026-03-15', '14:00:00', 1, 2200.00, 120),
(102, 15, '2026-03-16', '14:00:00', 3, 2200.00, 120),
(103, 29, '2026-03-16', '21:30:00', 4, 2200.00, 120),
(104, 12, '2026-03-16', '23:45:00', 1, 2200.00, 120),
(105, 6, '2026-03-16', '19:00:00', 1, 2200.00, 120),
(106, 28, '2026-03-16', '16:30:00', 1, 2200.00, 120),
(107, 12, '2026-03-17', '23:45:00', 5, 2200.00, 120),
(108, 3, '2026-03-17', '19:00:00', 4, 2200.00, 120),
(109, 19, '2026-03-17', '21:30:00', 4, 2200.00, 120),
(110, 14, '2026-03-17', '16:30:00', 1, 2200.00, 120),
(111, 2, '2026-03-17', '14:00:00', 1, 2200.00, 120),
(112, 9, '2026-03-18', '19:00:00', 3, 2200.00, 120),
(113, 19, '2026-03-18', '23:45:00', 2, 2200.00, 120),
(114, 27, '2026-03-18', '14:00:00', 1, 2200.00, 114),
(115, 3, '2026-03-18', '21:30:00', 1, 2200.00, 120),
(116, 11, '2026-03-18', '16:30:00', 1, 2200.00, 120),
(117, 2, '2026-03-19', '21:30:00', 2, 2200.00, 120),
(118, 9, '2026-03-19', '14:00:00', 2, 2200.00, 120),
(119, 3, '2026-03-19', '23:45:00', 3, 2200.00, 120),
(120, 25, '2026-03-19', '16:30:00', 4, 2200.00, 120),
(121, 1, '2026-03-19', '19:00:00', 5, 2200.00, 120),
(122, 15, '2026-03-20', '23:45:00', 3, 2200.00, 120),
(123, 7, '2026-03-20', '21:30:00', 1, 2200.00, 120),
(124, 21, '2026-03-20', '19:00:00', 4, 2200.00, 120),
(125, 11, '2026-03-20', '14:00:00', 2, 2200.00, 120),
(126, 14, '2026-03-20', '16:30:00', 4, 2200.00, 120),
(127, 29, '2026-03-21', '14:00:00', 2, 2200.00, 120),
(128, 26, '2026-03-21', '16:30:00', 5, 2200.00, 120),
(129, 11, '2026-03-21', '19:00:00', 2, 2200.00, 120),
(130, 4, '2026-03-21', '23:45:00', 4, 2200.00, 120),
(131, 2, '2026-03-21', '21:30:00', 5, 2200.00, 120),
(132, 22, '2026-03-22', '21:30:00', 2, 2200.00, 119),
(133, 6, '2026-03-22', '14:00:00', 2, 2200.00, 120),
(134, 30, '2026-03-22', '23:45:00', 4, 2200.00, 117),
(135, 10, '2026-03-22', '19:00:00', 4, 2200.00, 119),
(136, 20, '2026-03-22', '16:30:00', 4, 2200.00, 120),
(137, 11, '2026-03-23', '11:00:00', 4, 2200.00, 118),
(138, 24, '2026-03-23', '21:30:00', 5, 2200.00, 120),
(139, 30, '2026-03-23', '16:30:00', 3, 2200.00, 120),
(140, 12, '2026-03-23', '23:45:00', 2, 2200.00, 120),
(141, 6, '2026-03-23', '19:00:00', 3, 2200.00, 120),
(142, 19, '2026-03-24', '19:00:00', 3, 2200.00, 120),
(143, 10, '2026-03-24', '16:30:00', 2, 2200.00, 120),
(144, 13, '2026-03-24', '21:30:00', 3, 2200.00, 120),
(145, 17, '2026-03-24', '23:45:00', 2, 2200.00, 120),
(146, 18, '2026-03-24', '14:00:00', 4, 2200.00, 118),
(147, 23, '2026-03-25', '19:00:00', 2, 2200.00, 120),
(148, 18, '2026-03-25', '16:30:00', 3, 2200.00, 120),
(149, 24, '2026-03-25', '21:30:00', 3, 2200.00, 120),
(150, 6, '2026-03-25', '23:45:00', 4, 2200.00, 120),
(151, 21, '2026-03-25', '14:00:00', 2, 2200.00, 120),
(152, 28, '2026-03-26', '16:30:00', 3, 2200.00, 120),
(153, 7, '2026-03-26', '23:45:00', 2, 2200.00, 120),
(154, 19, '2026-03-26', '19:00:00', 3, 2200.00, 120),
(155, 5, '2026-03-26', '14:00:00', 4, 2200.00, 120),
(156, 21, '2026-03-26', '21:30:00', 4, 2200.00, 120),
(157, 18, '2026-03-27', '14:00:00', 1, 2200.00, 114),
(158, 23, '2026-03-27', '23:45:00', 5, 2200.00, 120),
(159, 6, '2026-03-27', '19:00:00', 4, 2200.00, 120),
(160, 26, '2026-03-27', '16:30:00', 5, 2200.00, 120),
(161, 27, '2026-03-27', '21:30:00', 4, 2200.00, 120),
(162, 25, '2026-03-28', '16:30:00', 5, 2200.00, 120),
(163, 10, '2026-03-28', '21:30:00', 5, 2200.00, 120),
(164, 11, '2026-03-28', '19:00:00', 3, 2200.00, 120),
(165, 18, '2026-03-28', '23:45:00', 3, 2200.00, 120),
(166, 3, '2026-03-28', '14:00:00', 3, 2200.00, 120),
(167, 4, '2026-03-29', '19:00:00', 4, 2200.00, 120),
(168, 8, '2026-03-29', '16:30:00', 3, 2200.00, 120),
(169, 24, '2026-03-29', '14:00:00', 4, 2200.00, 120),
(170, 21, '2026-03-29', '23:45:00', 1, 2200.00, 120),
(171, 22, '2026-03-29', '21:30:00', 3, 2200.00, 120),
(172, 20, '2026-03-30', '16:30:00', 2, 2200.00, 120),
(173, 15, '2026-03-30', '21:30:00', 5, 2200.00, 120),
(174, 22, '2026-03-30', '23:45:00', 4, 2200.00, 120),
(175, 18, '2026-03-30', '14:00:00', 4, 2200.00, 119),
(176, 9, '2026-03-30', '19:00:00', 2, 2200.00, 120),
(177, 22, '2026-03-31', '21:30:00', 2, 2200.00, 120),
(178, 13, '2026-03-31', '16:30:00', 1, 2200.00, 120),
(179, 19, '2026-03-31', '19:00:00', 2, 2200.00, 120),
(180, 10, '2026-03-31', '23:45:00', 4, 2200.00, 120),
(181, 17, '2026-03-31', '14:00:00', 5, 2200.00, 120),
(182, 3, '2026-04-01', '14:00:00', 1, 2200.00, 120),
(183, 8, '2026-04-01', '16:30:00', 5, 2200.00, 120),
(184, 27, '2026-04-01', '21:30:00', 2, 2200.00, 120),
(185, 19, '2026-04-01', '23:45:00', 1, 2200.00, 120),
(186, 6, '2026-04-01', '19:00:00', 2, 2200.00, 120),
(187, 19, '2026-04-02', '16:30:00', 5, 2200.00, 120),
(188, 29, '2026-04-02', '21:30:00', 1, 2200.00, 120),
(189, 10, '2026-04-02', '19:00:00', 4, 2200.00, 120),
(190, 27, '2026-04-02', '14:00:00', 3, 2200.00, 120),
(191, 4, '2026-04-02', '23:45:00', 3, 2200.00, 120),
(192, 15, '2026-04-03', '21:30:00', 5, 2200.00, 120),
(193, 26, '2026-04-03', '16:30:00', 3, 2200.00, 120),
(194, 28, '2026-04-03', '14:00:00', 5, 2200.00, 120),
(195, 3, '2026-04-03', '19:00:00', 1, 2200.00, 120),
(196, 19, '2026-04-03', '23:45:00', 1, 2200.00, 120),
(197, 18, '2026-04-04', '16:30:00', 4, 2200.00, 120),
(198, 11, '2026-04-04', '14:00:00', 1, 2200.00, 120),
(199, 6, '2026-04-04', '23:45:00', 4, 2200.00, 120),
(200, 22, '2026-04-04', '19:00:00', 3, 2200.00, 120),
(201, 16, '2026-04-04', '21:30:00', 5, 2200.00, 120),
(202, 20, '2026-04-05', '14:00:00', 5, 2200.00, 120),
(203, 23, '2026-04-05', '23:45:00', 2, 2200.00, 120),
(204, 5, '2026-04-05', '21:30:00', 4, 2200.00, 120),
(205, 22, '2026-04-05', '19:00:00', 5, 2200.00, 120),
(206, 9, '2026-04-05', '16:30:00', 4, 2200.00, 120),
(207, 3, '2026-04-06', '19:00:00', 2, 2200.00, 120),
(208, 27, '2026-04-06', '23:45:00', 3, 2200.00, 120),
(209, 7, '2026-04-06', '14:00:00', 5, 2200.00, 120),
(210, 13, '2026-04-06', '21:30:00', 2, 2200.00, 120),
(211, 18, '2026-04-06', '16:30:00', 4, 2200.00, 120),
(212, 9, '2026-04-07', '19:00:00', 5, 2200.00, 120),
(213, 14, '2026-04-07', '23:45:00', 2, 2200.00, 120),
(214, 11, '2026-04-07', '16:30:00', 2, 2200.00, 120),
(215, 5, '2026-04-07', '14:00:00', 4, 2200.00, 120),
(216, 10, '2026-04-07', '21:30:00', 2, 2200.00, 120),
(217, 1, '2026-04-08', '16:30:00', 5, 2200.00, 120),
(218, 6, '2026-04-08', '19:00:00', 3, 2200.00, 120),
(219, 18, '2026-04-08', '23:45:00', 2, 2200.00, 120),
(220, 11, '2026-04-08', '14:00:00', 2, 2200.00, 120),
(221, 7, '2026-04-08', '21:30:00', 3, 2200.00, 120),
(222, 11, '2026-04-09', '16:30:00', 5, 2200.00, 120),
(223, 17, '2026-04-09', '21:30:00', 3, 2200.00, 120),
(224, 29, '2026-04-09', '23:45:00', 4, 2200.00, 120),
(225, 2, '2026-04-09', '14:00:00', 1, 2200.00, 120),
(226, 10, '2026-04-09', '19:00:00', 4, 2200.00, 120),
(227, 5, '2026-04-10', '23:45:00', 4, 2200.00, 120),
(228, 29, '2026-04-10', '21:30:00', 5, 2200.00, 120),
(229, 14, '2026-04-10', '14:00:00', 3, 2200.00, 120),
(230, 17, '2026-04-10', '19:00:00', 5, 2200.00, 120),
(231, 8, '2026-04-10', '16:30:00', 5, 2200.00, 120),
(232, 11, '2026-04-11', '19:00:00', 4, 2200.00, 120),
(233, 12, '2026-04-11', '21:30:00', 4, 2200.00, 120),
(234, 14, '2026-04-11', '14:00:00', 5, 2200.00, 120),
(235, 7, '2026-04-11', '23:45:00', 2, 2200.00, 120),
(236, 29, '2026-04-11', '16:30:00', 5, 2200.00, 120),
(237, 20, '2026-04-12', '21:30:00', 1, 2200.00, 120),
(238, 15, '2026-04-12', '23:45:00', 4, 2200.00, 120),
(239, 27, '2026-04-12', '16:30:00', 5, 2200.00, 120),
(240, 4, '2026-04-12', '19:00:00', 1, 2200.00, 120),
(241, 25, '2026-04-12', '14:00:00', 2, 2200.00, 120),
(242, 4, '2026-04-13', '23:45:00', 5, 2200.00, 120),
(243, 10, '2026-04-13', '16:30:00', 2, 2200.00, 120),
(244, 9, '2026-04-13', '14:00:00', 2, 2200.00, 120),
(245, 7, '2026-04-13', '21:30:00', 4, 2200.00, 120),
(246, 25, '2026-04-13', '19:00:00', 4, 2200.00, 120),
(247, 3, '2026-04-14', '19:00:00', 1, 2200.00, 120),
(248, 22, '2026-04-14', '23:45:00', 2, 2200.00, 120),
(249, 13, '2026-04-14', '16:30:00', 1, 2200.00, 120),
(250, 28, '2026-04-14', '14:00:00', 3, 2200.00, 120),
(251, 26, '2026-04-14', '21:30:00', 5, 2200.00, 120),
(252, 12, '2026-04-15', '21:30:00', 3, 2200.00, 120),
(253, 28, '2026-04-15', '23:45:00', 3, 2200.00, 120),
(254, 2, '2026-04-15', '14:00:00', 5, 2200.00, 120),
(255, 18, '2026-04-15', '16:30:00', 2, 2200.00, 120),
(256, 30, '2026-04-15', '19:00:00', 2, 2200.00, 120),
(257, 14, '2026-04-16', '21:30:00', 2, 2200.00, 120),
(258, 25, '2026-04-16', '14:00:00', 2, 2200.00, 120),
(259, 27, '2026-04-16', '19:00:00', 1, 2200.00, 120),
(260, 5, '2026-04-16', '16:30:00', 3, 2200.00, 120),
(261, 13, '2026-04-16', '23:45:00', 1, 2200.00, 120),
(262, 1, '2026-04-17', '16:30:00', 3, 2200.00, 120),
(263, 20, '2026-04-17', '19:00:00', 3, 2200.00, 120),
(264, 29, '2026-04-17', '14:00:00', 4, 2200.00, 120),
(265, 19, '2026-04-17', '21:30:00', 4, 2200.00, 120),
(266, 7, '2026-04-17', '23:45:00', 3, 2200.00, 120),
(267, 13, '2026-04-18', '16:30:00', 5, 2200.00, 120),
(268, 27, '2026-04-18', '23:45:00', 3, 2200.00, 120),
(269, 10, '2026-04-18', '19:00:00', 4, 2200.00, 120),
(270, 14, '2026-04-18', '14:00:00', 4, 2200.00, 120),
(271, 8, '2026-04-18', '21:30:00', 3, 2200.00, 120);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `screening_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `purchase_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `price_paid` decimal(8,2) NOT NULL,
  `status` enum('active','used','cancelled') DEFAULT 'active',
  `payment_id` int(11) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- A tábla adatainak kiíratása `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `screening_id`, `seat_number`, `purchase_date`, `price_paid`, `status`, `payment_id`, `payment_status`) VALUES
(1, 1, 10, 'H08', '2026-02-26 10:33:03', 2200.00, 'active', 3, 'paid'),
(2, 4, 11, 'E07', '2026-02-26 10:35:25', 2200.00, 'active', 4, 'paid'),
(3, 4, 11, 'E08', '2026-02-26 10:35:25', 2200.00, 'active', 4, 'paid'),
(4, 4, 11, 'E09', '2026-02-26 10:35:25', 2200.00, 'active', 4, 'paid'),
(5, 1, 28, 'E08', '2026-03-02 08:39:50', 2200.00, 'active', 7, 'paid'),
(6, 4, 31, 'A01', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(7, 4, 31, 'A02', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(8, 4, 31, 'A03', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(9, 4, 31, 'A04', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(10, 4, 31, 'A05', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(11, 4, 31, 'A06', '2026-03-02 09:27:01', 2200.00, 'active', 10, 'paid'),
(12, 1, 37, 'E08', '2026-03-04 07:44:03', 2200.00, 'active', 12, 'paid'),
(13, 5, 71, 'E08', '2026-03-09 08:24:26', 2200.00, 'active', 13, 'paid'),
(14, 8, 114, 'F05', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(15, 8, 114, 'G04', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(16, 8, 114, 'G06', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(17, 8, 114, 'G07', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(18, 8, 114, 'H05', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(19, 8, 114, 'H06', '2026-03-18 07:29:36', 2200.00, 'active', 14, 'paid'),
(20, 1, 132, 'F08', '2026-03-22 16:46:49', 2200.00, 'active', 16, 'paid'),
(21, 1, 134, 'H08', '2026-03-22 16:47:42', 2200.00, 'active', 17, 'paid'),
(22, 1, 135, 'H08', '2026-03-22 16:57:56', 2200.00, 'active', 18, 'paid'),
(23, 1, 134, 'H09', '2026-03-22 17:30:14', 2200.00, 'active', 19, 'paid'),
(24, 1, 137, 'H09', '2026-03-22 18:00:07', 2200.00, 'active', 21, 'paid'),
(25, 5, 134, 'E08', '2026-03-22 18:55:05', 2200.00, 'active', 22, 'paid'),
(26, 1, 137, 'H08', '2026-03-23 08:48:31', 2200.00, 'active', 23, 'paid'),
(50, 1, 10, 'H08', '2026-02-26 09:33:03', 2200.00, 'active', 3, 'paid'),
(51, 1, 146, 'A08', '2026-03-23 09:51:29', 2200.00, 'active', 24, 'paid'),
(52, 1, 146, 'A09', '2026-03-23 09:51:29', 2200.00, 'active', 24, 'paid'),
(53, 1, 157, 'E05', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(54, 1, 157, 'E06', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(55, 1, 157, 'E07', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(56, 1, 157, 'E10', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(57, 1, 157, 'E11', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(58, 1, 157, 'E12', '2026-03-27 10:45:02', 2200.00, 'active', 25, 'paid'),
(59, 1, 175, 'E08', '2026-03-30 07:34:52', 2200.00, 'active', 26, 'paid');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `registration_date`, `last_login`) VALUES
(1, 'admin', 'admin@mozi.hu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Adminisztrátor', 'admin', '2026-02-26 10:25:56', '2026-04-01 15:43:47'),
(2, 'felhasznalo2', 'felhasznalo2@pelda.com', '$2y$10$e88U.FqHZ6Gv2vY5gm9zmuQN9V1qkdDVqX8hBUgtMg2jc3QxDch7.', 'felhasznalo2', 'user', '2026-02-26 10:25:56', NULL),
(3, 'felhasznalo', 'felhasznalo@pelda.com', '$2y$10$TkxbuI8UTHCV6Yyhn4VdBe3rsxTF7GEkCIWQEy6uA2c1Z9HsJThr2', 'felhasznal', 'user', '2026-02-26 10:25:56', NULL),
(4, 'proba', 'proba@gmail.hu', '$2y$10$/NXLnXKs0HWj0fthRdXlJ.yipMQgay3JBPoKvBTMtnEkuBwwk9ipi', 'proba', 'user', '2026-02-26 10:34:08', '2026-03-12 10:13:37'),
(5, 'KDominik69', 'kmetzdominik69@gmail.com', '$2y$10$ob2TTIqgcdwrS0JtyleSaO2HlwQu21PT7g.gL8HDmWZFTnumX/2HG', 'Kmetz Dominik', 'user', '2026-03-09 08:23:17', '2026-03-23 16:22:57'),
(6, 'jagrili', 'digisulim@gmail.com', '$2y$10$YwCr/bj1a07DIsQhBv9CReXj6gKukj3Lf..Y7Cbi6NGhmm2/Aegg.', 'Jágri Ilona', 'user', '2026-03-12 09:59:34', '2026-03-12 09:59:46'),
(7, 'hoppá', 'hoppa@gmail.com', '$2y$10$8wbFDU6PAJxVDIniP881werr.q3rvRJursYecRob/16aF2iaty6hW', 'hoppáhoppáhoppá', 'user', '2026-03-12 10:15:25', '2026-03-12 10:15:46'),
(8, 'asd2', 'asd2@gmail.com', '$2y$10$kbCFjs0HMifsbn8CA99xnOnTn.Jv1BAvGt06CUY5EyqlxzgfC3DEC', 'csicska', 'user', '2026-03-18 07:27:09', '2026-03-18 07:27:22'),
(9, 'probauj', 'asd@h.com', '$2y$10$bS/zrpRzDe6wF/8doQEZBOuJndkXSnI./B8kZUQII37Ugqasg4YAy', 'Proba', 'user', '2026-03-20 11:42:13', '2026-03-20 11:42:22');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- A tábla indexei `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `screening_id` (`screening_id`),
  ADD KEY `idx_stripe_session` (`stripe_session_id`),
  ADD KEY `idx_status` (`status`);

--
-- A tábla indexei `screenings`
--
ALTER TABLE `screenings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- A tábla indexei `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `screening_id` (`screening_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT a táblához `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT a táblához `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT a táblához `screenings`
--
ALTER TABLE `screenings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;

--
-- AUTO_INCREMENT a táblához `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`screening_id`) REFERENCES `screenings` (`id`);

--
-- Megkötések a táblához `screenings`
--
ALTER TABLE `screenings`
  ADD CONSTRAINT `screenings_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`screening_id`) REFERENCES `screenings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
