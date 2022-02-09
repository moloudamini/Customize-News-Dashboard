-- add column category_label to category table
ALTER TABLE user__field_uw_news_categories
ADD COLUMN category_label VARCHAR(255) AFTER field_uw_news_categories_value;

-- update category table based on category_label
UPDATE user__field_uw_news_categories 
SET category_label = CASE 
    WHEN field_uw_news_categories_value = '31' THEN 'Awards, Honours and Rankings' 
    WHEN field_uw_news_categories_value = '32' THEN 'Entrepreneurship' 
    WHEN field_uw_news_categories_value = '33' THEN 'Technology and Human Health' 
    WHEN field_uw_news_categories_value = '34' THEN 'Advancements in Big and Small Manufacturing' 
    WHEN field_uw_news_categories_value = '35' THEN 'Sustainable Planet' 
    WHEN field_uw_news_categories_value = '38' THEN 'Social and Economic Prosperity' 
    WHEN field_uw_news_categories_value = '40' THEN 'Quantum-Nano Revolution' 
    WHEN field_uw_news_categories_value = '43' THEN 'Community' 
    WHEN field_uw_news_categories_value = '69' THEN 'Research' 
    WHEN field_uw_news_categories_value = '70' THEN 'Talent' 
    WHEN field_uw_news_categories_value = '78' THEN 'Co-op and Experiential Education' 
    WHEN field_uw_news_categories_value = '82' THEN 'Next-Generation Computin'
    WHEN field_uw_news_categories_value = '99' THEN 'Human-Machine Interaction' 
    WHEN field_uw_news_categories_value = '100' THEN 'Transformational Discoveries' 
    WHEN field_uw_news_categories_value = '136' THEN 'Global Impact' 
    WHEN field_uw_news_categories_value = '15' THEN 'Self' 
    END
WHERE user__field_uw_news_categories IN ('31','32','33','34', '35','38','40','43', '69','70','78','82','99','100','136','15')

 -- add column language to language table  
ALTER TABLE user__field_language
ADD COLUMN language VARCHAR(255) AFTER field_language_value;

-- update language table based on language value
UPDATE user__field_language
SET language = CASE 
	WHEN field_language_value = "all" THEN "All"
    WHEN field_language_value = "ar" THEN "Arabic"
    WHEN field_language_value ="de" THEN "German"
    WHEN field_language_value ="en" THEN "English"
    WHEN field_language_value ="es" THEN "Spanish"
    WHEN field_language_value ="fr" THEN "French"
    WHEN field_language_value ="he" THEN "Hebrew"
    WHEN field_language_value ="it" THEN "Italian"
    WHEN field_language_value ="nl" THEN "Dutch"
    WHEN field_language_value ="no" THEN "Norwegian"
    WHEN field_language_value ="pt" THEN "Portuguese"
    WHEN field_language_value ="ru" THEN "Russian"
    WHEN field_language_value ="se" THEN "Swedish"
    WHEN field_language_value ="ud" THEN "Czech Republic"
    WHEN field_language_value ="zh" THEN "Chinese"
    END
WHERE field_language_value IN ('all','ar','de', 'en','es','fr','he', 'it','nl','no','pt','ru','se','ud','zh');

-- add column countries to country table
ALTER TABLE user__field_countries
ADD COLUMN countries VARCHAR(255) AFTER field_countries_value;

-- update country table based on countries value
UPDATE user__field_countries
SET countries = CASE 
    WHEN field_countries_value ="ae" THEN "United Arab Emirates"
    WHEN field_countries_value ="ar" THEN "Argentina"
    WHEN field_countries_value ="at" THEN "Austria"
    WHEN field_countries_value ="au" THEN "Australia"
    WHEN field_countries_value ="be" THEN "Belgium"
    WHEN field_countries_value ="bg" THEN "Bulgaria"
    WHEN field_countries_value ="br" THEN "Brazil"
    WHEN field_countries_value ="ca" THEN "Canada"
    WHEN field_countries_value ="ch" THEN "Switzerland"
    WHEN field_countries_value ="cn" THEN "China"
    WHEN field_countries_value ="co" THEN "Colombia"
    WHEN field_countries_value ="cu" THEN "Cuba"
    WHEN field_countries_value ="cz" THEN "Czech Republic"
    WHEN field_countries_value ="de" THEN "Germany"
    WHEN field_countries_value ="eg" THEN "Egypt"
    WHEN field_countries_value ="fr" THEN "France"
    WHEN field_countries_value ="gb" THEN "United Kingdom"
    WHEN field_countries_value ="gr" THEN "Greece"
    WHEN field_countries_value ="hk" THEN "Hong Kong"
    WHEN field_countries_value ="hu" THEN "Hungary"
    WHEN field_countries_value ="id" THEN "Indonesia"
    WHEN field_countries_value ="ie" THEN "Ireland"
    WHEN field_countries_value ="il" THEN "Israel"
    WHEN field_countries_value ="in" THEN "India"
    WHEN field_countries_value ="it" THEN "Italy"
    WHEN field_countries_value ="jp" THEN "Japan"
    WHEN field_countries_value ="kr" THEN "Korea, Republic of"
    WHEN field_countries_value ="lt" THEN "Lithuania"
    WHEN field_countries_value ="lv" THEN "Latvia"
    WHEN field_countries_value ="ma" THEN "Morocco"
    WHEN field_countries_value ="mx" THEN "Mexico"
    WHEN field_countries_value ="my" THEN "Malaysia"
    WHEN field_countries_value ="ng" THEN "Nigeria"
    WHEN field_countries_value ="nl" THEN "Netherlands"
    WHEN field_countries_value ="no" THEN "Norway"
    WHEN field_countries_value ="nz" THEN "New Zealand"
    WHEN field_countries_value ="ph" THEN "Philippines"
    WHEN field_countries_value ="pl" THEN "Poland"
    WHEN field_countries_value ="pt" THEN "Portugal"
    WHEN field_countries_value ="ro" THEN "Romania"
    WHEN field_countries_value ="rs" THEN "Serbia"
    WHEN field_countries_value ="ru" THEN "Russian Federation"
    WHEN field_countries_value ="sa" THEN "Saudi Arabia"
    WHEN field_countries_value ="se" THEN "Sweden"
    WHEN field_countries_value ="sg" THEN "Singapore"
    WHEN field_countries_value ="si" THEN "Slovenia"
    WHEN field_countries_value ="sk" THEN "Slovakia"
    WHEN field_countries_value ="th" THEN "Thailand"
    WHEN field_countries_value ="tr" THEN "Turkey"
    WHEN field_countries_value ="tw" THEN "Taiwan, Province of China"
    WHEN field_countries_value ="ua" THEN "Ukraine"
    WHEN field_countries_value ="us" THEN "United States"
    WHEN field_countries_value ="ve" THEN "Venezuela, Bolivarian Republic of"
    WHEN field_countries_value ="za" THEN "South Africa"
    END

-- the relationship between the number of users in each news field and different news categories and sources
SELECT news_fields, category_label, MAX(user)AS max_user_in_category, SUM(user)AS total_user_in_field, t4.sources
FROM (SELECT entity_id, delta, field_are_you_interested_mostly__value as news_fields, COUNT(*) AS user, category_label
    FROM user__field_are_you_interested_mostly_ 
    INNER JOIN (SELECT entity_id, delta, category_label FROM user__field_uw_news_categories) AS t2
    USING(entity_id, delta)
    GROUP BY category_label, news_fields
    ORDER BY user DESC) AS t3
LEFT JOIN (SELECT entity_id, delta, field_sources_value AS sources FROM user__field_sources)AS t4
ON t3.entity_id = t4.entity_id AND t3.delta = t4.delta
GROUP BY news_fields
ORDER BY total_user_in_field DESC;

-- the relationship between the number of users in each category and different countries and languages
SELECT category_label, SUM(user)AS total_user_in_category, MAX(user)AS max_user_in_category,countries AS max_user_in_country, t2.language
FROM (SELECT entity_id, delta, category_label, countries, count(*) as user
    FROM user__field_uw_news_categories
    INNER JOIN user__field_countries
    USING(entity_id,delta)
    GROUP BY category_label,countries
    ORDER BY user DESC) as t1
LEFT JOIN (SELECT entity_id, delta, language FROM user__field_language) AS t2
USING (entity_id, delta)
GROUP BY category_label
ORDER BY total_user_in_category DESC; 

-- the relationship between the top 20 countries with max users and different news categories and languages
SELECT countries, SUM(user) AS total_user_in_country, category_label AS category_with_max_user, MAX(user) AS max_user_for_category,t2.language
FROM(SELECT entity_id, delta, category_label, countries, COUNT(*) AS user
      FROM user__field_uw_news_categories 
      INNER JOIN user__field_countries 
      USING(entity_id,delta)
      GROUP BY category_label,countries
      ORDER BY user DESC)AS t1
LEFT JOIN (SELECT entity_id, delta, language FROM user__field_language) AS t2
USING (entity_id, delta)
GROUP BY countries
ORDER BY max_user_for_category DESC 
LIMIT 20;

-- the relationship between the number of users in each category and different news language and countries
SELECT category_label, sum(user)as total_user_with_all_languages, language as max_use_with_language, max(user)as max_user_for_category, t2.countries
FROM (SELECT entity_id, delta, category_label, language, COUNT(*) as user
      FROM user__field_uw_news_categories 
      INNER JOIN user__field_language
      USING(entity_id,delta)
      GROUP BY language,category_label
      ORDER BY user DESC)as t1
LEFT JOIN (SELECT entity_id, delta, countries FROM user__field_countreis) AS t2
USING (entity_id, delta)
GROUP BY category_label
ORDER BY total_user_with_all_languages DESC
LIMIT 20;

-- the relationship between the top 20 news sources with max users and different categories and countries
SELECT news_sources, SUM(user) AS total_user_with_this_source, category_label AS category_with_max_user, MAX(user)AS max_user_for_category, t2.countries
FROM (SELECT entity_id, delta, category_label, field_sources_value AS news_sources, COUNT(*) AS user
      FROM user__field_uw_news_categories 
      INNER JOIN user__field_sources
      USING(entity_id,delta)
      GROUP BY category_label,field_sources_value
      ORDER BY user DESC)AS t1
INNER JOIN (SELECT entity_id, delta, countries FROM user__field_countries) AS t2
USING(entity_id, delta)
GROUP BY news_sources
ORDER BY max_user_for_category DESC
LIMIT 20;

-- the relationship between the number of users in each news source and different categories and languages
SELECT news_sources, SUM(user) AS total_user_with_this_source, category_label AS category_with_max_user, MAX(user)AS max_user_for_category, t2.language
FROM (SELECT entity_id, delta, category_label, field_sources_value AS news_sources, COUNT(*) AS user
      FROM user__field_uw_news_categories 
      INNER JOIN user__field_sources
      USING(entity_id,delta)
      GROUP BY category_label,field_sources_value
      ORDER BY user DESC)AS t1
INNER JOIN (SELECT entity_id, delta, language FROM user__field_language) AS t2
USING(entity_id, delta)
GROUP BY news_sources
ORDER BY max_user_for_category DESC
LIMIT 20;

-- the relationship between the number of users with different keywords and different news categories and countreis
SELECT news_keywords, SUM(user)AS total_user_with_this_keyword, category_label AS category_with_max_user, MAX(user)AS max_user_for_category,t2.countries
FROM (SELECT entity_id, delta, category_label, field_keywords_value AS news_keywords, COUNT(*) AS user
      FROM user__field_uw_news_categories 
      INNER JOIN user__field_keywords
      USING(entity_id,delta)
      GROUP BY category_label,field_keywords_value
      ORDER BY user DESC)AS t1
INNER JOIN (SELECT entity_id, delta, countries FROM user__field_countries) AS t2
USING(entity_id,delta)
GROUP BY news_keywords  
ORDER BY max_user_for_category  DESC
LIMIT 20;