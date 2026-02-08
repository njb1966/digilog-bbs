-- ============================================
-- Seed Data for Community BBS
-- ============================================

-- Insert default subs (message boards)
INSERT INTO subs (name, slug, description, position) VALUES
('General Discussion', 'general', 'The main discussion area for anything and everything.', 1),
('Retro & BBS Culture', 'retro', 'Dial-up stories, BBS memories, ANSI art, door games, and message networks.', 2),
('Projects & Monthly Challenges', 'projects', 'Community projects, skill building, monthly coding/building challenges, and accountability threads.', 3),
('Sysop & BBS Workshop', 'sysop', 'Configuration help, message base setup, door games, mods, and troubleshooting.', 4),
('Retro Hardware & Emulation', 'hardware', 'Vintage PC builds, CRTs, sound cards, recaps, DOSBox, and preservation.', 5),
('Modern Tech & Self-Hosting', 'modern-tech', 'Linux/BSD, homelabs, networking, security, VMs, and practical self-hosting.', 6),
('Creative Corner', 'creative', 'ANSI art, pixel art, music, writing, zines, and photography.', 7),
('Philosophy & Big Questions', 'philosophy', 'Ethics, meaning, identity, technology''s impact - thoughtful, slower discussions.', 8),
('The Coffeehouse', 'coffeehouse', 'Low-pressure hangout. Daily threads, "what are you working on?", casual chat.', 9),
('Games & Media', 'games', 'Door games, tabletop, video games, movies, music - story and experience focused.', 10);

-- Create a test admin user
-- Password: "password" (hashed with PASSWORD_DEFAULT - bcrypt)
-- You should change this immediately after first login!
INSERT INTO users (username, email, password_hash, is_admin, is_active, email_verified) VALUES
('admin', 'admin@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE, TRUE);

-- Create some test users
INSERT INTO users (username, email, password_hash, is_active, email_verified) VALUES
('alice', 'alice@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE),
('bob', 'bob@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE),
('charlie', 'charlie@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE);

-- Insert some test messages
INSERT INTO messages (sub_id, user_id, parent_id, subject, body, created_at) VALUES
-- General Discussion
(1, 2, NULL, 'Welcome to the board', 'Hey everyone, glad to see this place coming together. Looking forward to some good discussions without all the noise and chaos of the usual platforms.\n\nAnyone else here from the old dial-up BBS days?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 3, 1, 'Re: Welcome to the board', '> Anyone else here from the old dial-up BBS days?\n\nAbsolutely! Started on a 2400 baud modem back in ''89. Ran a small WWIV board in the early 90s.\n\nThe simplicity of this setup reminds me of those days, but without the phone line constraints.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 2, 2, 'Re: Welcome to the board', '> The simplicity of this setup reminds me of those days,\n> but without the phone line constraints.\n\nThat''s exactly the goal. Keep the intimacy and focus of classic BBS culture, but make it accessible via web.\n\nI think the key differences that make this work:\n- Chronological reading (not endless nested threads)\n- No gamification (no likes, upvotes, karma)\n- Plain text focus (monospace, controlled formatting)\n- "New since last visit" tracking\n- Small community size by design\n\nModern forums optimized for scale and SEO. This optimizes for conversation and return visits.', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 1, NULL, 'Testing message threading', 'Just curious how replies look when there are multiple threads going at once. The chronological flow is interesting - different from typical forums.\n\nSeems like it would work well for smaller, more engaged communities where people actually read everything.', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(1, 2, 3, 'Re: Welcome to the board', 'Love it. Already feels different from the usual forum chaos.\n\nQuestion: are you planning to add any door game support? TradeWars or LORD would be amazing here.', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),

-- The Coffeehouse
(9, 2, NULL, 'What are you working on this week?', 'Figured I''d start a regular check-in thread.\n\nThis week I''m:\n- Setting up this BBS properly\n- Working through some old Pascal code\n- Trying to get an old 486 running again\n\nHow about you?', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(9, 3, 6, 'Re: What are you working on this week?', 'Nice! I''m diving into some Rust tutorials and trying to wrap my head around the borrow checker.\n\nAlso attempting to document my homelab setup before I forget how everything is wired together.', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Projects
(3, 2, NULL, 'Monthly Challenge - January: Write a door game', 'First monthly challenge!\n\nGoal: Write a simple door game in any language. Could be a number guessing game, a simple maze, text adventure, whatever.\n\nPost your progress here. End of month we''ll share what we built.\n\nWho''s in?', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Set up read pointers for test users
-- Alice has read everything in General up to message #3
INSERT INTO read_pointers (user_id, sub_id, last_read_message_id, updated_at) VALUES
(2, 1, 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 9, 7, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Bob has read everything
INSERT INTO read_pointers (user_id, sub_id, last_read_message_id, updated_at) VALUES
(3, 1, 5, NOW()),
(3, 9, 7, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 3, 8, DATE_SUB(NOW(), INTERVAL 4 DAY));
