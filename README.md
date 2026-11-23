# Attempts per day for Moodle quizzes (quizaccess_dailyattempts)

Limits how many attempts a student can start in any 24 hours. Built for practice quizzes with unlimited attempts, where some students retry the same quiz twenty times in an evening until they get a lucky score, instead of going back to the material.

## Use it

In the quiz settings, under **Extra restrictions on attempts**, set **Attempts per 24 hours** (1 to 10, or Unlimited).

When a student reaches the limit, the quiz page tells them when they can start the next attempt, and the attempt button goes away until then. The quiz page also shows the limit to everyone.

## Good to know

- It is a rolling 24 hours, not a calendar day: with a limit of 3, a student who started attempts at 9:00, 10:00 and 11:00 can start the next one after 9:00 the next day. This works the same in every time zone.
- Started attempts count, including abandoned ones, so leaving an attempt unfinished does not give an extra try. Teacher previews never count.
- It works alongside the quiz's own "Attempts allowed" and "Enforced delay between attempts". When no attempts are left anyway, those settings explain why.
- If the quiz closes before the student's next attempt would be allowed, they are told so.
- No personal data is stored: the rule counts the attempts the quiz already records. The setting is kept in backups and restores.

Moodle 4.5 to 5.3. Built by DevLMS: https://devlms.com
