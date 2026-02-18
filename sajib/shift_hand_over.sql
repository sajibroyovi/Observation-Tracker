-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 07:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shift_hand_over`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaign`
--

CREATE TABLE `campaign` (
  `serial_no` int(11) NOT NULL,
  `campaign_name` text NOT NULL,
  `start_date` date NOT NULL,
  `status` varchar(80) NOT NULL,
  `description` varchar(254) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaign`
--

INSERT INTO `campaign` (`serial_no`, `campaign_name`, `start_date`, `status`, `description`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(1, 'Mail Subjects:\n DIU Canteen_MP-KA_bKash Funded Cashback  Campaign_Jan-26_Tech\n M/S Nexario_MP-KA_Merchant Funded Cashback Campaign_Jan-26 (Technology Feedback)\n DITF Key Furniture_bKash Funded Cashback Campaign_Jan-26_Tech\n\nSR Details:\nSR ID: 375916\nSR ID: 376385\n\nDuration (Date and Time):\n10 January 2026, 00:00:00 Hours\n\nInitiator Details (Stakeholder):\nFahima Akther Nitu (01707246778)\nHabiba Islam (01708436572)\nAtia Ibnat Riya (01728196487)\n\nPOC Details (Configuration Team):\nMst. Fariha Rashid (01758513036)', '2026-01-10', 'active', 'Initiator number: 017454536', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(2, 'DIU Canteen_MP-KA_bKash Funded Cashback Campaign_Jan-26_Tech', '2026-01-10', 'completed', 'Details (Stakeholder): Fahima Akther Nitu (01707246778) Habiba Islam (01708436572) Atia Ibnat Riya (01728196487) POC Details (Configuration Team): Mst. Fariha Rashid (01758513036)', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'DIU Canteen_MP-KA_bKash Funded Cashback Campaign_Jan-26_Tech  M/S Nexario_MP-KA_Merchant Funded Cashback Campaign_Jan-26 (Technology Feedback) 3. DITF Key Furniture_bKash Funded Cashback Campaign_Jan-26_Tech SR', '2026-01-10', 'active', 'Fahima Akther Nitu (01707246778) Habiba Islam (01708436572) Atia Ibnat Riya (01728196487) POC Details (Configuration Team): Mst. Fariha Rashid (01758513036)', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'DIU Canteen_MP-KA_bKash Funded Cashback Campaign_Jan-26_Tech M/S Nexario_MP-KA_Merchant Funded Cashback Campaign_Jan-26 (Technology Feedback) DITF Key Furniture_bKash Funded Cashback Campaign_Jan-26_Tech SR', '2026-01-10', 'active', 'Fahima Akther Nitu (01707246778) Habiba Islam (01708436572) Atia Ibnat Riya (01728196487) POC Details (Configuration Team): Mst. Fariha Rashid (01758513036)', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `cr_list`
--

CREATE TABLE `cr_list` (
  `serial_no` int(11) NOT NULL,
  `cr_subject` varchar(254) NOT NULL,
  `impacted_area` varchar(254) NOT NULL,
  `cr_start_time` datetime NOT NULL,
  `cr_end_time` datetime NOT NULL,
  `downtime` tinyint(254) NOT NULL,
  `cr_meeting_attended` varchar(254) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cr_list`
--

INSERT INTO `cr_list` (`serial_no`, `cr_subject`, `impacted_area`, `cr_start_time`, `cr_end_time`, `downtime`, `cr_meeting_attended`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(2, 'change request 2937u49', 'Mobile recharge\r\nUSSD\r\n', '2026-01-09 20:27:00', '2026-01-10 22:27:00', 0, 'Sajib', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'change request 23958', 'GP mobile recharge', '2026-01-10 12:12:00', '2026-01-11 12:12:00', 1, 'Tirtha', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'Change Request 29248', 'Customer app', '2026-01-11 22:55:00', '2026-01-12 22:55:00', 0, 'Sajib', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `enable_disable`
--

CREATE TABLE `enable_disable` (
  `serial_no` int(11) NOT NULL,
  `service_name` varchar(80) NOT NULL,
  `action_date` datetime NOT NULL,
  `action_taken` tinyint(50) NOT NULL,
  `action_taken_by` varchar(50) NOT NULL,
  `reference` varchar(256) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enable_disable`
--

INSERT INTO `enable_disable` (`serial_no`, `service_name`, `action_date`, `action_taken`, `action_taken_by`, `reference`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(1, 'Agrani Bank', '2026-01-10 00:00:00', 1, 'Sajib Roy', 'jahdfjhdjkfhjkahdkjahfjhsad', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(2, 'BPDB postpaid', '2026-01-09 00:00:00', 1, 'Sajib Roy', 'ak;hfjd', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'DESCO', '2026-01-09 21:26:00', 2, 'Tirtho', 'jahdfjhdjkfhjkahdkjahfjhsad', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(5, 'AESCO', '2026-01-13 19:30:00', 2, 'Simran Madam', 'File NESCO', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(6, 'BDBL Postpaid', '2026-01-10 21:00:00', 0, 'Simran Madam', 'BDBL Disable', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(7, 'Demo service', '2026-01-10 21:00:00', 1, 'John Doe', 'Web team', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(9, 'Mobile Recharge', '2026-01-11 17:25:00', 0, 'Sajib Roy', 'Disable Mobile recharge', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(10, 'PGW', '2026-01-11 22:16:00', 0, 'Sha Newaz', 'Disable PGW', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(11, 'dsafd', '2026-01-11 18:31:00', 3, 'Sha Newaz', 'Disable PGW', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(12, 'dfafddf', '2026-01-11 18:33:00', 3, 'Mostafiz', 'Disable PGW', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(13, 'City Bank', '2026-01-11 18:42:00', 2, 'Fahim', 'Hide City Bank', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `handover`
--

CREATE TABLE `handover` (
  `id` int(11) NOT NULL,
  `shift` varchar(10) DEFAULT NULL,
  `handover_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `handover`
--

INSERT INTO `handover` (`id`, `shift`, `handover_date`, `created_at`) VALUES
(1, 'Night', '2026-01-11', '2026-01-11 09:44:52'),
(2, 'Morning', '2026-01-12', '2026-01-11 10:20:25'),
(3, 'Evening', '2026-01-10', '2026-01-11 10:22:07'),
(4, 'Night', '2026-01-11', '2026-01-11 10:24:16'),
(5, 'Morning', '2026-01-11', '2026-01-11 10:29:15'),
(6, 'Morning', '2026-01-11', '2026-01-11 11:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `observations`
--

CREATE TABLE `observations` (
  `serial_no` int(11) NOT NULL,
  `observation_names` text NOT NULL,
  `team_name` text NOT NULL,
  `start_date` datetime NOT NULL,
  `l1_observation` text NOT NULL,
  `l1_image` varchar(255) DEFAULT NULL,
  `l1_image_2` varchar(255) DEFAULT NULL,
  `l1_observations_by` varchar(50) DEFAULT NULL,
  `l2_observation` text DEFAULT NULL,
  `l2_observations_by` varchar(50) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `observations`
--

INSERT INTO `observations` (`serial_no`, `observation_names`, `team_name`, `start_date`, `l1_observation`, `l1_image`, `l1_image_2`, `l1_observations_by`, `l2_observation`, `l2_observations_by`, `created_by`, `created_at`, `edited_by`, `edited_at`) VALUES
(1, 'Observing sudden drop on TLK ussd, airtime', 'Tech Service Operations, Tech Service Delivery', '2026-02-09 08:02:00', 'fdasfag\r\nafadf\r\nsdafsdaf\r\nasdfsadf', 'uploads/698c0a42ac771_1.png', 'uploads/698c0a42ae2c3_2.png', 'superadmin', 'hgjhadf kh;ladflafd lhljadfasdf jkja\';dfsjadsf j\';ja\'dfj\'adfsasdf j;\'afdadf\'kj ;jjkfdjhkdaf lhjkh;af h;lafdjh ;hadfh;adf ;hhadsf;h dfh;sdaf;adf adfh;adf\r\nkdjklajdf\r\nkagdfkjadf\r\ngakldfadf', 'Samira', 'superadmin', '2026-02-09 02:03:39', 'Sajib_5891', '2026-02-11 04:49:06'),
(2, 'Observed Robi Airtime_Increased dfadf', 'Tech Service Operations, Network Operations', '2026-02-09 08:44:00', 'ghgadf zfgsgfsgfdg agfga  adfdaf ddarwert dfatrrt aagfgart adfadfaerqe faferq fdsafaewra aerwerfsadfawaqr afarera weewfadfwer\r\nbbadfh\r\njhakdshf\r\njhkadhsf\r\nh;dasfdff\r\njhf;afd\r\nafhafd', 'uploads/698c0a58aff93_1.png', 'uploads/698c0a58b1607_2.png', 'Simran_5187', '', '', 'Simran_5187', '2026-02-09 02:45:03', 'Sajib_5891', '2026-02-11 04:49:28'),
(3, 'Observed Robi Airtime_Increased dfadf', 'Tech Service Operations, Tech Service Delivery', '2026-02-11 10:47:00', 'dfgsdhsgh\r\nhjdgfjd\r\nghdjdk\r\nhjdjdgj', 'uploads/698c0a1036bfc_1.png', 'uploads/698c0a1037b22_2.png', 'Sajib_5891', '', '', 'Sajib_5891', '2026-02-11 04:48:16', NULL, NULL),
(4, 'Observed Robi Airtime_Increased dfadf', 'Tech Service Operations, Tech Service Delivery', '2026-02-15 13:43:00', 'Network was okay, 3days pattern', 'uploads/69915d4ed6bda_1.png', 'uploads/69915d4ed8627_2.png', 'Simran_5187', '', '', 'Simran_5187', '2026-02-15 05:44:46', NULL, NULL),
(5, 'Getting CAS Aler', 'Network Operations', '2026-02-16 18:38:00', 'hdfgdfh\r\nghfghfffffffffffffffffffhghhjhjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjkklkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 'uploads/6993100f1e652_1.png', 'uploads/6993100f2252f_2.png', 'Simran_5187', '', '', 'Simran_5187', '2026-02-16 12:39:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pending_mail`
--

CREATE TABLE `pending_mail` (
  `serial_no` int(11) NOT NULL,
  `subject_line` varchar(250) NOT NULL,
  `priority` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_mail`
--

INSERT INTO `pending_mail` (`serial_no`, `subject_line`, `priority`, `status`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(2, 'Registration request 09/01/2026', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'Bank info add 09/01/2026', 'low', 'answered', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'First subject line', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(5, 'Second subject line', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(6, 'Third subject line', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(7, 'Bank in add', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(8, 'Bank inclusion', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(9, 'Bank account needed', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(10, 'Demo Subject 1', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(11, 'Demo Subject 2', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(12, 'Final status required', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(13, 'Final Status required', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(14, 'fghfj', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(15, 'Sonali Bank add money', 'medium', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(16, 'Sonali Bank add money', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(17, 'City Bank transfers money', 'medium', 'answered', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(18, 'City Bank Transfer Money', 'medium', 'answered', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `promo_banner`
--

CREATE TABLE `promo_banner` (
  `serial_no` int(11) NOT NULL,
  `subject_line` varchar(254) NOT NULL,
  `status` varchar(254) NOT NULL,
  `start_time` datetime NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_banner`
--

INSERT INTO `promo_banner` (`serial_no`, `subject_line`, `status`, `start_time`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(2, 'Agent Hero banner', 'scheduled', '2026-01-10 00:00:00', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'Push Notification', 'draft', '2026-01-11 00:00:00', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'Tread Letter', 'live', '2026-01-11 00:00:00', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(5, 'Cohort banner', 'inactive', '2026-01-11 00:00:00', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(6, 'Customer app hero banner', 'scheduled', '2026-01-12 00:00:00', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `security_mail`
--

CREATE TABLE `security_mail` (
  `serial_no` int(11) NOT NULL,
  `subject_line` varchar(254) NOT NULL,
  `priority` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_mail`
--

INSERT INTO `security_mail` (`serial_no`, `subject_line`, `priority`, `status`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(1, 'Feedback required @bkash.com', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(2, 'Feedback required @bkash.com', 'low', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'Feedback required @bkash.com', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'feedback required', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(6, 'check the mail @baksh.com', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(7, 'check the below mail @gamil.com', 'high', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(8, 'Feedback required demo@gmail.com', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(9, 'adfhjkadf', 'medium', 'follow_up', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(10, 'lmmlzmvvc', 'high', 'pending', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `service_outage`
--

CREATE TABLE `service_outage` (
  `serial_no` int(11) NOT NULL,
  `details` text NOT NULL,
  `incident_id` int(11) NOT NULL,
  `problem_ticket` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `technician` varchar(50) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_outage`
--

INSERT INTO `service_outage` (`serial_no`, `details`, `incident_id`, `problem_ticket`, `status`, `technician`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(1, 'Event Name: ROBI Service Outage\nImpact: Customers are unable to avail robi ussd, airtime and sms service.\nEvent Type: Exception\nOutage Type: Complete\nReason: Network connectivity issue\nMajor: Yes\nPriority: P2\nStatus: Resolved\nDuration: 07:28 AM to 07:32 AM, 7th January 2026\n\nNote: Vendor has been informed, they are checking.\n\nPreface: This is the initial incident notification circulation from CMC. Above information might need to be modified depending on the further investigation outcome, we will update accordingly.', 330404, 605, 'resolved', 'sajib', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(2, 'Event Name: ROBI Service Outage\r\nImpact: Customers are unable to avail robi ussd, airtime and sms service.\r\nEvent Type: Exception\r\nOutage Type: Complete\r\nReason: Network connectivity issue\r\nMajor: Yes\r\nPriority: P2\r\nStatus: Resolved\r\nDuration: 07:28 AM to 07:32 AM, 7th January 2026\r\n\r\nNote: Vendor has been informed, they are checking.\r\n\r\nPreface: This is the initial incident notification circulation from CMC. Above information might need to be modified depending on the further investigation outcome, we will update accordingly.', 330404, 605, 'in_progress', 'sajib', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'Event Name: bKash Loan Service Outage\r\nImpact: Customers were unable to avail loan services in the bKash app.\r\nEvent Type: Exception\r\nOutage Type: Intermittent\r\nReason: AWS Lambda instance issue for the Loan service.\r\nMajor: Yes\r\nPriority: P2\r\nStatus: Resolved\r\nDuration: From 3:42 PM to 5:02 PM, 6th January 2026\r\n\r\nAction Taken:\r\nAfter a manual restart of the one Corrupted Lambda instance at 5:01 PM, the service was restored.', 33456, 609, 'pending', 'Jihad', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(4, 'Event Name: Internal East–West Firewall Intermittent Issues from ST to Colo\r\n\r\nImpact: Customers and bKash employees were intermittently facing service outages for the following impacted services:\r\n\r\nImpacted Services:\r\n\r\n* Coupon\r\n* Statement from Customer App\r\n* Statement from Agent App\r\n* CIMT Transaction Details (Data API)\r\n* Omnichannel Live Chat (customers unable to reach live chat)\r\n* Customers landing in IVR as non-customers (IVR to CPS API call)\r\n* ISMS Portal inaccessible\r\n* IRM\r\n\r\nEvent Type: Exception\r\nOutage Type: Intermittent\r\nReason: Under investigation\r\nMajor: P2\r\nPriority: Major\r\nStatus: Resolved\r\nDuration: 01:15 AM to 02:27 AM 30th December 2025\r\n\r\nNote: After rollback the  Firewall (EW) Inclusion for Coupon,Omnichannel,ISMS, IRM, Data API Services activity the service restore.', 334948, 601, 'in_progress', 'Herrok Das', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(5, 'Event Name: Agent app login outage.\r\nImpact: All agents were unable to login the app.\r\nEvent Type: Exception\r\nOutage Type: Complete\r\nReason: Issue happened due to the misconfiguration of agent app version from CMS.\r\nMajor: Yes\r\nPriority: P1\r\nStatus: Resolved\r\nDuration: 04:10 AM to 08:55 AM, 29th December 2025.\r\n', 333498, 0, 'pending', 'Rafia Munjarinn', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `ssl_certificate`
--

CREATE TABLE `ssl_certificate` (
  `serial_no` int(11) NOT NULL,
  `certificate_name` varchar(254) NOT NULL,
  `expiration_date` date NOT NULL,
  `renewal_status` varchar(80) NOT NULL,
  `issues` varchar(254) NOT NULL,
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ssl_certificate`
--

INSERT INTO `ssl_certificate` (`serial_no`, `certificate_name`, `expiration_date`, `renewal_status`, `issues`, `edited_by`, `edited_at`, `created_by`, `created_at`) VALUES
(2, 'Sonali bank SSL certificate', '2026-01-15', 'pending', 'No issues', NULL, NULL, NULL, '2026-02-09 01:59:11'),
(3, 'Agrani Bank SSL certificate', '2026-01-31', 'pending', 'Certificate will expire on 31st Jan 2026', NULL, NULL, NULL, '2026-02-09 01:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','l1','l2') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowed_modules` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT 'system',
  `edited_by` varchar(50) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `allowed_modules`, `created_by`, `edited_by`, `edited_at`) VALUES
(1, 'superadmin', '$2y$10$rTt3ejJq0p8NeCfuAelYPequVNDuxWpzQ80Jpdr65GBhvSzhNZ33S', 'super_admin', '2026-02-09 01:46:14', 'Enable/Disable,Pending Mail,Security Mail,CR List,Promo Banner,Service Outage,SSL Certificate,Campaign,Observations', 'system', NULL, NULL),
(2, 'admin', '$2y$10$/sjPseEcJUVY55udfdmSR.9udHinicypUUEJFkhGp0cKNEM1D8EsW', 'admin', '2026-02-09 01:46:14', 'Enable/Disable,Pending Mail,Security Mail,CR List,Promo Banner,Service Outage,SSL Certificate,Campaign,Observations', 'system', NULL, NULL),
(5, 'Simran_5187', '$2y$10$sFQhKWWrDoCfUoVTyPqqROvrYDyw.I3qs0qIsUEhyIU.n10sBz4XW', 'l1', '2026-02-09 02:11:34', 'Observations', 'superadmin', NULL, NULL),
(6, 'Rafia', '$2y$10$73L/l18fwgW4BRPzuIr0IOrLf/E2Z3hWWnxIA9IvWGklvr0FX6mZO', 'l2', '2026-02-09 02:12:26', 'Observations', 'superadmin', 'superadmin', '2026-02-09 02:26:23'),
(7, 'Samira', '$2y$10$CNJXp3Nc/i9.33w68GYnz..JMid0HIHY6ux4IOUAjnrFfkYNvzhn2', 'l2', '2026-02-09 02:23:58', 'Observations', 'superadmin', NULL, NULL),
(8, 'Sajib_5891', '$2y$10$nDGcTPGA3NjhfuFXtsXFpephLdYRuUq8wwtjSa/wyUwXh3a9cff4S', 'super_admin', '2026-02-11 04:32:47', 'Enable/Disable,Pending Mail,Security Mail,Promo Banner', 'superadmin', NULL, NULL),
(9, 'ridoy', '$2y$10$OzcoIL29AxLqBNSeHd9ije9q7yCsAKjs2hKELhRm.GWRp5kkcv.TK', 'super_admin', '2026-02-15 06:01:31', '', 'superadmin', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaign`
--
ALTER TABLE `campaign`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `cr_list`
--
ALTER TABLE `cr_list`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `enable_disable`
--
ALTER TABLE `enable_disable`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `handover`
--
ALTER TABLE `handover`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `observations`
--
ALTER TABLE `observations`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `pending_mail`
--
ALTER TABLE `pending_mail`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `promo_banner`
--
ALTER TABLE `promo_banner`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `security_mail`
--
ALTER TABLE `security_mail`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `service_outage`
--
ALTER TABLE `service_outage`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `ssl_certificate`
--
ALTER TABLE `ssl_certificate`
  ADD PRIMARY KEY (`serial_no`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaign`
--
ALTER TABLE `campaign`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cr_list`
--
ALTER TABLE `cr_list`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enable_disable`
--
ALTER TABLE `enable_disable`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `handover`
--
ALTER TABLE `handover`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `observations`
--
ALTER TABLE `observations`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pending_mail`
--
ALTER TABLE `pending_mail`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `promo_banner`
--
ALTER TABLE `promo_banner`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_mail`
--
ALTER TABLE `security_mail`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service_outage`
--
ALTER TABLE `service_outage`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ssl_certificate`
--
ALTER TABLE `ssl_certificate`
  MODIFY `serial_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
