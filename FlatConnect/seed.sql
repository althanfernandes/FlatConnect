-- ======================================================
-- FlatConnect Sample Seed Data
-- ======================================================

USE `flatconnect_db`;

-- Disable foreign key checks for clean re-insertion
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `saved_properties`;
TRUNCATE TABLE `enquiries`;
TRUNCATE TABLE `replacement_posts`;
TRUNCATE TABLE `roommate_posts`;
TRUNCATE TABLE `property_images`;
TRUNCATE TABLE `properties`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insert Test Users
-- All accounts use password: Password@123
-- Hash: $2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe
INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password`, `role`, `preferred_location`, `max_budget`, `preferred_type`, `preferred_furnishing`, `bio`) VALUES
(1, 'Rajesh Sharma', 'owner@flatconnect.com', '9822011223', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'owner', 'Hinjewadi', 25000, 'Flat', 'Fully Furnished', 'Experienced flat and PG owner with properties across Hinjewadi, Wakad and Tathawade.'),
(2, 'Amit Deshmukh', 'tenant@flatconnect.com', '9890123456', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'tenant', 'Wakad', 12000, 'Flat', 'Semi-Furnished', 'Software engineer currently renting a 2BHK flat in Wakad; looking for replacement tenant and roommates.'),
(3, 'Pooja Kulkarni', 'seeker@flatconnect.com', '9765432100', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'seeker', 'Hinjewadi', 18000, 'Flat', 'Semi-Furnished', 'Relocating to Pune for IT job in Hinjewadi Phase 2. Looking for clean, quiet flat or PG.'),
(4, 'Sunil Patil', 'sunil.owner@flatconnect.com', '9822033445', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'owner', 'Baner', 30000, 'Flat', 'Fully Furnished', 'Real estate owner in Baner and Punawale offering bachelor-friendly rental spaces.'),
(5, 'Priya Mehta', 'priya.tenant@flatconnect.com', '9822055667', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'tenant', 'Tathawade', 8000, 'PG', 'Fully Furnished', 'Final year engineering student looking for flatmate and successor for rented room.'),
(6, 'Rahul Joshi', 'rahul.seeker@flatconnect.com', '9822077889', '$2y$10$Z1Jwm9uHWq85YSyk3SBObuY6k1xy7aa2djLOImgoItLjWXOg0ONfe', 'seeker', 'Tathawade', 9000, 'Room', 'Semi-Furnished', 'Master student at JSPM looking for budget accommodation nearby.');

-- 2. Insert 12 Pune Properties
INSERT INTO `properties` (`id`, `owner_id`, `title`, `property_type`, `location`, `address`, `rent`, `deposit`, `furnishing`, `facilities`, `bachelor_allowed`, `available_from`, `is_urgent`, `description`, `contact_phone`, `status`) VALUES
(1, 1, 'Green View Apartment – Tathawade', 'Flat', 'Tathawade', 'Near Dange Chowk, Tathawade, Pune 411033', 18000, 40000, 'Semi-Furnished', 'WiFi, Geyser, Lift, Power Backup, Covered Parking, Security Guard', 'Yes', CURDATE(), 1, 'Spacious 2BHK flat with natural sunlight, modular kitchen, 2 balconies. Bachelors and students are welcome. No brokerage.', '9822011223', 'Available'),

(2, 1, 'Sunshine PG – Wakad', 'PG', 'Wakad', 'Datta Mandir Road, Wakad, Pune 411057', 7500, 15000, 'Fully Furnished', 'WiFi, Food Included, RO Water, Housekeeping, Washing Machine, CCTV', 'Boys Only', CURDATE(), 1, 'Well-maintained PG for boys and working men. Single and double sharing available. 3 times hygienic food, high-speed WiFi.', '9822011223', 'Available'),

(3, 1, 'Blue Nest Residency – Hinjewadi', 'Flat', 'Hinjewadi', 'Phase 1, Near Infosys Circle, Hinjewadi, Pune 411057', 14000, 30000, 'Fully Furnished', 'AC, WiFi, Geyser, Fridge, Washing Machine, 24x7 Water', 'Yes', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 0, 'Cozy 1BHK apartment right next to IT Park. Walkable to major tech parks. Fully equipped with modern electronics.', '9822011223', 'Available'),

(4, 4, 'Comfort Living – Punawale', 'Flat', 'Punawale', 'Near Mumbai-Pune Highway, Punawale, Pune 411033', 16500, 35000, 'Semi-Furnished', 'Geyser, Lift, Gym, Swimming Pool, Car Parking, Security Guard', 'Yes', CURDATE(), 1, 'Brand new 2BHK apartment in gated society with full amenities. Excellent connectivity to expressway and Hinjewadi IT park.', '9822033445', 'Available'),

(5, 4, 'Urban Stay – Baner', 'Flat', 'Baner', 'Near Balewadi High Street, Baner, Pune 411045', 28000, 60000, 'Fully Furnished', 'AC, Modular Kitchen, Sofa, TV, Power Backup, Clubhouse, 24x7 Security', 'Yes', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 0, 'Luxurious 3BHK flat in prime Baner locality. Walking distance to cafes, restaurants and metro corridor.', '9822033445', 'Available'),

(6, 1, 'Sai Residency – Tathawade', 'Room', 'Tathawade', 'Behind Indira College, Tathawade, Pune 411033', 8000, 16000, 'Unfurnished', '24x7 Water, Bike Parking, Attached Washroom, Separate Electricity Meter', 'Yes', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 0, 'Independent 1RK room ideal for college students or single professionals. Very peaceful surroundings.', '9822011223', 'Available'),

(7, 4, 'Happy Homes – Wakad', 'Flat', 'Wakad', 'Kaspate Vasti, Wakad, Pune 411057', 20000, 45000, 'Fully Furnished', 'Beds, Wardrobes, Fridge, Geyser, Gas Pipeline, Lift, Parking', 'Family Only', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 0, 'Premium 2BHK family apartment in quiet neighbourhood. Close to schools, supermarkets and hospitals.', '9822033445', 'Available'),

(8, 1, 'City Nest – Pimpri', 'Flat', 'Pimpri', 'Near Pimpri Metro Station, Pimpri, Pune 411018', 11000, 25000, 'Semi-Furnished', 'Geyser, Kitchen Cabinets, Lift, Water Purifier, Bike Parking', 'Yes', CURDATE(), 1, 'Pocket-friendly 1BHK flat close to metro station and industrial hub. Low maintenance and convenient access.', '9822011223', 'Available'),

(9, 1, 'New Life Apartment – Hinjewadi', 'Room', 'Hinjewadi', 'Phase 2, Near Tech Mahindra, Hinjewadi, Pune 411057', 9000, 18000, 'Fully Furnished', 'AC, WiFi, Attached Bathroom, Balcony, Cleaning Service', 'Yes', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0, 'Single private room in a premium serviced apartment. Daily cleaning and high-speed internet included.', '9822011223', 'Available'),

(10, 4, 'College Corner PG – Tathawade', 'PG', 'Tathawade', 'Opposite JSPM Campus, Tathawade, Pune 411033', 6500, 12000, 'Fully Furnished', 'Food Included, WiFi, Study Table, Washing Machine, 24x7 Guard', 'Girls Only', CURDATE(), 1, 'Safe, clean and friendly girls PG directly opposite engineering college gate. Biometric entry and caring warden.', '9822033445', 'Available'),

(11, 4, 'Peaceful Stay – Punawale', 'Flat', 'Punawale', 'Malwadi Road, Punawale, Pune 411033', 15000, 30000, 'Unfurnished', 'Covered Parking, 24x7 Water, Balcony, Solar Water, Lift', 'Yes', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 0, 'Affordable 2BHK flat with scenic hill views. Ideal for small families or working roommates with their own furniture.', '9822033445', 'Available'),

(12, 1, 'Maple Residency – Baner', 'Flat', 'Baner', 'Pan Card Club Road, Baner, Pune 411045', 24000, 50000, 'Fully Furnished', 'WiFi, AC, Smart TV, Modular Kitchen, Sofa Set, Reserved Parking', 'Yes', CURDATE(), 1, 'Stylish 2BHK flat ready to move in today. Modern interiors, high-end appliances, peaceful society.', '9822011223', 'Available');

-- 3. Insert Property Images
INSERT INTO `property_images` (`property_id`, `image_path`, `is_primary`) VALUES
(1, 'assets/images/properties/prop1.svg', 1),
(2, 'assets/images/properties/prop2.svg', 1),
(3, 'assets/images/properties/prop3.svg', 1),
(4, 'assets/images/properties/prop4.svg', 1),
(5, 'assets/images/properties/prop5.svg', 1),
(6, 'assets/images/properties/prop6.svg', 1),
(7, 'assets/images/properties/prop7.svg', 1),
(8, 'assets/images/properties/prop8.svg', 1),
(9, 'assets/images/properties/prop9.svg', 1),
(10, 'assets/images/properties/prop10.svg', 1),
(11, 'assets/images/properties/prop11.svg', 1),
(12, 'assets/images/properties/prop12.svg', 1);

-- 4. Insert 6 Roommate Profiles
INSERT INTO `roommate_posts` (`id`, `user_id`, `occupation`, `preferred_location`, `budget`, `sharing_preference`, `lifestyle_preferences`, `move_in_date`, `contact_number`, `notes`, `status`) VALUES
(1, 6, 'Working Professional', 'Hinjewadi', 7000, '2 Sharing', 'Non-smoker, Vegetarian, Early riser, Clean habits', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '9822077889', 'Working as a QA Engineer at Hinjewadi Phase 1. Looking for a quiet flatmate to share rent.', 'Active'),

(2, 2, 'Working Professional', 'Wakad', 8500, 'Single Room', 'IT employee, Non-drinker, Peaceful environment, Work-from-home', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '9890123456', 'I have a 2BHK flat in Wakad and looking for a friendly roommate for the private master bedroom.', 'Active'),

(3, 5, 'Student', 'Tathawade', 5500, '3 Sharing', 'Engineering student, Night owl, Friendly, Tea lover', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '9822055667', 'Studying CS in Tathawade. Looking for fellow college students to share a spacious 3BHK flat nearby.', 'Active'),

(4, 3, 'Working Professional', 'Baner', 10000, '2 Sharing', 'Fintech professional, Hygiene conscious, Gym enthusiast', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '9765432100', 'Moving to Pune next month. Looking for a neat flatmate in Baner or Balewadi area.', 'Active'),

(5, 6, 'Working Professional', 'Punawale', 6500, '2 Sharing', 'Hybrid work, Weekend gamer, Respects privacy', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '9822077889', 'Looking for an easy-going roommate in Punawale or Wakad. Flexible with move-in dates.', 'Active'),

(6, 5, 'Student', 'Tathawade', 6000, 'Single Room', 'Book reader, Non-smoker, Focused on studies', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '9822055667', 'Seeking single room occupancy in a flat or shared house near campus.', 'Active');

-- 5. Insert 4 Tenant Replacement Posts
INSERT INTO `replacement_posts` (`id`, `tenant_id`, `property_name`, `location`, `rent`, `deposit`, `available_from`, `sharing_details`, `facilities`, `description`, `contact_preference`, `status`) VALUES
(1, 2, 'Blue Ridge Tower 14 – Hinjewadi Phase 1', 'Hinjewadi', 8500, 20000, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '1 Bed in 2BHK Shared Flat', 'WiFi, AC, Washing Machine, Geyser, Modular Kitchen, Gym, Swimming Pool', 'I am shifting to Bangalore due to job switch. Need an urgent replacement for my spot in a fully furnished 2BHK. Two other cool roommates already living here.', 'Call / WhatsApp on 9890123456', 'Active'),

(2, 2, 'Kasturi Apostle – Wakad Datta Mandir', 'Wakad', 7000, 15000, CURDATE(), 'Private Room in 3BHK Flat', 'WiFi, Maid for cooking & cleaning, Fridge, Water Purifier, Covered Parking', 'Immediate replacement needed for single private room. Flat is nicely furnished with low monthly maintenance. Very safe society.', 'WhatsApp preferred on 9890123456', 'Active'),

(3, 5, 'Shrinath Empire – Tathawade JSPM Road', 'Tathawade', 5000, 10000, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '1 Bed in 2BHK Student Flat', 'WiFi, Gas Stove, Geyser, RO Water, Bike Parking, Security', 'Graduating this semester and relocating back to hometown. Rent is only ₹5000 per month including water. Walkable distance to college.', 'Call between 10 AM to 8 PM on 9822055667', 'Active'),

(4, 2, 'Balewadi High Street Residency – Baner', 'Baner', 12000, 25000, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Master Bedroom in 2BHK Luxury Flat', 'Air Conditioner, Attached Balcony, Smart TV, Automatic Washing Machine, Lift', 'Moving out by end of month. Luxury room with attached washroom and private balcony. Owner is chill and no interference.', 'Call directly on 9890123456', 'Active');

-- 6. Insert Sample Enquiries
INSERT INTO `enquiries` (`id`, `property_id`, `replacement_id`, `roommate_post_id`, `sender_id`, `receiver_id`, `message`, `response`, `status`) VALUES
(1, 1, NULL, NULL, 3, 1, 'Hello Rajesh ji, is Green View Apartment available for immediate shift? I am a working professional at Hinjewadi.', 'Yes Pooja, the flat is available immediately and bachelors are allowed. You can visit today for inspection.', 'Replied'),
(2, 3, NULL, NULL, 3, 1, 'Hi, I saw Blue Nest Residency. Can I visit this weekend around 4 PM to inspect the flat?', NULL, 'Pending'),
(3, NULL, 1, NULL, 3, 2, 'Hi Amit, I am interested in taking over your spot at Blue Ridge Tower. When can we discuss?', 'Hi Pooja! You are welcome to visit tomorrow evening or we can connect over phone.', 'Replied'),
(4, NULL, NULL, 2, 6, 2, 'Hey Amit, saw your post for Wakad roommate. Is the private room still available?', NULL, 'Pending');

-- 7. Insert Sample Saved Properties for Seeker (user_id = 3)
INSERT INTO `saved_properties` (`user_id`, `property_id`) VALUES
(3, 1),
(3, 3),
(3, 5);
