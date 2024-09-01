# Obsidian Live Sync Web Companion

## Overview

This project is a demonstrator for a web companion based on Live Sync plugin for Obsidian, created as a quick start with the help of ChatGPT. The goal is to explore the potential of having an online companion for Obsidian’s Live Sync feature. Possible features include:

- A simple task list extracted from your Obsidian notes.
- A list of contacts or calendar entries based on YAML front matter.
- Public sharing of individual Obsidian notes (e.g., password-protected).

### Project Vision

The ultimate goal of this project is to attract someone more knowledgeable in CouchDB and Obsidian Live Sync to help push this project forward. I’m willing to support and collaborate with anyone interested in developing this further.

## How to Run It

1. **Clone the Repository:**

Clone this repo

2. **Set Up Configuration:**

   - Copy `config.php.example` to `config.php`:
   - Edit `config.php` with your Obsidian Live Sync settings.

3. **Run the Local PHP Server:**

    Start this in the directory to where you cloned the repo.
   ```bash
   php -S localhost:8000
   ```

4. Open your browser at http://localhost:8000

5. **Note on Task Limit:**

   - If you don’t see tasks in the output, you might need to increase the `limit` in `config.php`. In my case, I needed to go through the first few hundred results before tasks appeared.

## Contributing

If you’re knowledgeable in CouchDB and interested in contributing to this project, I’d love to hear from you. Your expertise could help turn this proof of concept into a fully functional tool. Please reach out if you’re interested in collaborating.
