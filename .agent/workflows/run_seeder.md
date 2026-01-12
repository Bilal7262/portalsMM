---
description: Run the database seeder to populate initial data including the new specialized voice.
---

1. Open a terminal in the project root `e:\bot middleman\portalsMM`.
2. Run the following command to seed the database:
   ```powershell
   php artisan db:seed --class=ScaleSeeder
   ```
   
   **Note**: This will truncate existing data in relevant tables (Company, Agents, Voices, etc.) and repopulate them.
