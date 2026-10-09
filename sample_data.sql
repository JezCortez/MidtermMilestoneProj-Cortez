-- OPTIONAL demo data. Import AFTER database.sql.
-- Demo accounts (both use the password:  Password1)
--   maria@example.com   and   ben@example.com

USE recipe_site;

INSERT INTO users (name, email, password_hash) VALUES
    ('Maria', 'maria@example.com', '$2y$10$3i56pxuue.fWD4k7MSwNxOzkEXkv7Y8LErISR261pyjaZutvOFIVm'),
    ('Ben',   'ben@example.com',   '$2y$10$3i56pxuue.fWD4k7MSwNxOzkEXkv7Y8LErISR261pyjaZutvOFIVm');

INSERT INTO recipes (user_id, category_id, title, description, steps, base_servings, created_at) VALUES
    (1, 1, 'Fluffy Pancakes', 'Soft, golden pancakes made with pantry staples. Great with fruit or syrup.',
     'Whisk the flour, sugar, baking powder and salt in a large bowl.\nIn another bowl, mix the milk, egg and melted butter.\nPour the wet mixture into the dry and stir until just combined (a few lumps are fine).\nHeat a lightly oiled pan over medium heat.\nPour about 1/4 cup of batter per pancake and cook until bubbles form, then flip.\nCook another minute until golden and serve warm.', 4, NOW() - INTERVAL 3 DAY),
    (2, 3, 'One-Pan Garlic Chicken & Rice', 'A simple weeknight dinner that cooks in one pan with very little cleanup.',
     'Season the chicken with salt and pepper.\nBrown the chicken in a large pan with oil, then set it aside.\nCook the garlic for 30 seconds, then add the rice and stir for a minute.\nPour in the broth, return the chicken, cover and simmer for 20 minutes.\nLet it rest for 5 minutes before serving.', 4, NOW() - INTERVAL 2 DAY),
    (1, 4, 'Easy Tomato Soup', 'A smooth, comforting soup that comes together in 30 minutes.',
     'Soften the onion and garlic in butter in a pot.\nAdd the tomatoes, broth and a pinch of salt.\nSimmer for 20 minutes.\nBlend until smooth and season to taste.', 4, NOW() - INTERVAL 1 DAY),
    (2, 7, 'Classic Banana Bread', 'Moist banana bread that is a good way to use up ripe bananas.',
     'Heat the oven to 175 C (350 F) and grease a loaf pan.\nMash the bananas and mix in the melted butter.\nStir in the sugar, egg and vanilla.\nFold in the flour, baking soda and salt until just combined.\nPour into the pan and bake for 55 to 60 minutes.\nCool before slicing.', 8, NOW());

INSERT INTO ingredients (recipe_id, position, quantity, unit, name) VALUES
    (1, 0, 1.5,   'cup',   'all-purpose flour'),
    (1, 1, 2,     'tbsp',  'sugar'),
    (1, 2, 2,     'tsp',   'baking powder'),
    (1, 3, 1,     'pinch', 'salt'),
    (1, 4, 1.25,  'cup',   'milk'),
    (1, 5, 1,     'pc',    'egg'),
    (1, 6, 3,     'tbsp',  'melted butter'),
    (2, 0, 500,   'g',     'chicken thighs'),
    (2, 1, 1.5,   'cup',   'long-grain rice'),
    (2, 2, 3,     'clove', 'garlic, minced'),
    (2, 3, 3,     'cup',   'chicken broth'),
    (2, 4, 2,     'tbsp',  'cooking oil'),
    (2, 5, NULL,  NULL,    'salt and pepper, to taste'),
    (3, 0, 1,     'pc',    'onion, chopped'),
    (3, 1, 2,     'clove', 'garlic'),
    (3, 2, 2,     'tbsp',  'butter'),
    (3, 3, 800,   'g',     'canned tomatoes'),
    (3, 4, 500,   'ml',    'vegetable broth'),
    (4, 0, 3,     'pc',    'ripe bananas'),
    (4, 1, 80,    'g',     'melted butter'),
    (4, 2, 150,   'g',     'sugar'),
    (4, 3, 1,     'pc',    'egg'),
    (4, 4, 1,     'tsp',   'vanilla extract'),
    (4, 5, 250,   'g',     'all-purpose flour'),
    (4, 6, 1,     'tsp',   'baking soda'),
    (4, 7, 1,     'pinch', 'salt');

INSERT INTO comments (recipe_id, user_id, body) VALUES
    (1, 2, 'Made these this morning, the whole family loved them!'),
    (4, 1, 'Great way to use up my overripe bananas. I added a handful of walnuts.');

INSERT INTO favorites (user_id, recipe_id) VALUES (2, 1), (1, 4);
