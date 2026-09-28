# Scavenger Hunt App — Setup & Adaptation Guide

A single-page web app for running a team scavenger hunt: teams register, pick which spots to visit, send a photo + an answer for each, organizers approve photo and answer separately, and a live leaderboard (with a 2-hour clock and a late penalty) updates on everyone's phone.

It ships configured for a Paris hunt (Notre-Dame → Sorbonne Université, Jussieu), but everything city-specific lives in a few clearly marked places in the HTML file.

## What's in the box

| File | Role |
|---|---|
| `index.html` | The whole app (HTML + CSS + JS). Rename it as you like. |
| `login.php` | *Optional.* Checks the admin PIN on the server (PHP alternative only). |

## How it works (in 30 seconds)

- The app is static: it runs entirely in the browser.
- Shared data (teams, submissions, photos, clock) is stored in a free **Firebase Realtime Database**, under three nodes: `teams`, `submissions`, `game`. Every phone listens live, so updates appear within about a second.
- Photos are resized in the browser (max 640 px wide, JPEG) and stored as text inside the database — no file storage needed.
- The map uses MapLibre + OpenFreeMap tiles (free, no API key, works worldwide).

---

## Step 1 — Create the Firebase database (both alternatives)

Free, no credit card, about 10 minutes.

1. Go to <https://console.firebase.google.com>, sign in with any Google account, and click **Add project** (Analytics can stay off).
2. **Build → Realtime Database → Create Database.** Choose a region close to your event and start in test mode.
3. Open the **Rules** tab, paste the following, and **Publish**:
   ```json
   { "rules": { ".read": true, ".write": true } }
   ```
4. **Project settings (gear icon) → Your apps →** click the `</>` web icon and register an app (any nickname, no Hosting needed).
5. Firebase shows a `firebaseConfig` object. Copy its values into the `firebaseConfig` block near the top of the `<script>` in the HTML file, replacing every `PASTE_...` placeholder. Use the exact `databaseURL` Firebase gives you (depending on the region it can end in `.firebaseio.com` or `.firebasedatabase.app`).

> **Heads-up on the open rules.** Anyone who knows your database URL can read and write it. That is fine for a short, low-stakes event; delete the Firebase project afterwards if you prefer. Neither alternative below changes this (see "Security, honestly" at the end).

If the config is missing or wrong, the app shows an orange warning banner instead of failing silently.

---

## Step 2 — Choose how the admin PIN is checked

The organizer area (start the clock, approve photos and answers, log arrivals, reset) is protected by a PIN. You pick where that PIN lives with one setting in `HUNT_CONFIG` (near the top of the script).

| | **A. Pure HTML** | **B. PHP** |
|---|---|---|
| `HUNT_CONFIG.adminMode` | `'local'` | `'php'` |
| Where the PIN lives | In the HTML file (`HUNT_CONFIG.localAdminPin`) | In `index.php` on your server (`$correctPin`) |
| Hosting needed | Any static host: GitHub Pages, Netlify, a school web space, even a USB-served page | A host that runs PHP (most shared hosting) |
| Files to upload | 1 (the HTML) | 2 (the HTML + `index.php`, same folder) |
| Can players find the PIN in "view source"? | **Yes** | No |
| Setup effort | Lowest | Slightly higher |

### Alternative A — Pure HTML (PIN in the file)

1. In `HUNT_CONFIG`, set:
   ```js
   adminMode:     'local',
   localAdminPin: 'choose-your-pin',
   ```
2. Fill in `firebaseConfig` (Step 1).
3. Upload the HTML file to any static host and share its URL with the teams.

Simple and robust, but treat the PIN as a courtesy lock, not a secret: anyone who opens the page source can read it. Choose a PIN you don't reuse anywhere.

### Alternative B — PHP (PIN kept on the server)

1. In `HUNT_CONFIG`, keep:
   ```js
   adminMode:     'php',
   adminEndpoint: 'login.php',
   ```
2. Open `login.php` and set your PIN on this line:
   ```php
   $correctPin = "2026";
   ```
3. Fill in `firebaseConfig` (Step 1).
4. Upload **both files to the same folder** of a PHP-capable host.
5. Share the **direct URL of the HTML file** (e.g. `https://your-site.example/hunt/index.html`).

How it works: when someone types a PIN, the page POSTs it to `login.php`, which answers only `{"success":true}` or `{"success":false}`. The PIN itself is never sent to the browser.

Watch out for:
- **Static hosts can't run PHP.** On GitHub Pages / plain Netlify the check fails and the app tells you so. Use Alternative A there.

### Testing either alternative

1. Open the page, create a team on **Home**.
2. Open **Admin**, enter the PIN, click **Start the clock**.
3. Submit a photo + answer from a second device; approve the photo and the answer separately in **Admin**.
4. Check the **Leaderboard**, then use **Reset everything** (Admin → Danger zone) to wipe the test data before the real event.

---

## Step 3 — Adapt the hunt to another place or event

All edits are inside the single `<script>` of the HTML file. Search for the names in `code font`.

### 3.1 Header, wording and map — `HUNT_CONFIG`

| Setting | What it does |
|---|---|
| `pageTitle` | Browser tab title |
| `eyebrow`, `title`, `subtitle` | The three lines in the header banner |
| `finishShort` | Short name of the finish, used in "Time's up — head to …!" and the late-penalty text |
| `finishLong` | Full name of the finish, shown above the map |
| `mapCenter` | Where the map opens, as `[longitude, latitude]` |
| `mapZoom` | Initial zoom (about 11–12 for a city centre, 14–15 for a campus or small town) |
| `adminMode`, `localAdminPin`, `adminEndpoint` | Admin PIN handling (Step 2) |

Remember that map coordinates are **longitude first** in `mapCenter`, but **latitude first** in `MISSION_MAP_POINTS` (below). That is the classic gotcha.

### 3.2 Timing and scoring — constants near `NAME_SUGGESTIONS`

| Constant | Meaning | Default in the file |
|---|---|---|
| `HUNT_DURATION_MS` | Length of the hunt in milliseconds. `2*60*60*1000` = 2 h; `90*60*1000` = 90 min. (The Paris file currently ends in `+120`, a harmless 120 ms leftover — remove it if you want exactly 2 h.) | 2 h |
| `LATE_PENALTY_PER_MIN` | Points removed per minute late (net score never goes below 0) | 6 |
| `QUESTION_POINTS` | Flat points for a correct answer; the rest of each spot's points go to the photo | 20 |

The "2h max" label on the Home tab is plain text in `renderHome()` — update it if you change the duration.

### 3.3 The places — `LOCATIONS`

Each spot is one object:

```js
{
  id:'my-spot',                 // unique, lowercase, no spaces — also used in MISSION_MAP_POINTS
  cluster:'Old Town',           // must exactly match an entry in CLUSTER_ORDER
  points:60,                    // total value; QUESTION_POINTS of it goes to the answer
  name:'Central Fountain',      // title shown on the card and map pin
  place:'Market Square',        // small line under the title (address or area)
  memo:`Short story or history, shown once the team has attached their photo.`,
  question:'How many statues surround the fountain?',
  answerKey:'6.',               // admin-only reference answer
  photoTask:'Team photo with the fountain behind you.'
}
```

Guidelines:
- **Points**: the Paris hunt scales points with how big a detour a spot is from the finish (40 = right by the finish, 60 = close, 80 = real detour, 100 = across the river, 130 = far side). Keep something like that so teams have real choices to make. Every `points` value must be greater than `QUESTION_POINTS`.
- **Questions**: prefer things checkable on site (counting, colours, shapes) over things a phone can answer in seconds. If an answer is inherently subjective, say so in `answerKey` (the admin sees it).
- **Time-sensitive answers** (e.g. "current mayor", menu prices) can go stale — recheck before each event.
- **Venue rules**: state them in `photoTask` (e.g. "no photos inside", "skip the security queue").
- **A finish-line mission** works well: the Paris file has a `sorbonne-universite` spot at the conference entrance so teams get a final photo.

### 3.4 Groups of spots — `CLUSTER_ORDER`

A list of cluster names, in the order they appear on the Missions tab. Every `cluster` used in `LOCATIONS` must appear here, otherwise those spots won't be displayed. Two tips:
- Group spots by walking proximity, and order clusters from cheapest to most demanding.
- Keep `LOCATIONS` sorted in the **same cluster order** as `CLUSTER_ORDER`. Map pins are numbered by position in `LOCATIONS`, cards by cluster order, so this keeps the two numberings identical.

### 3.5 Map pins — `MISSION_MAP_POINTS`

One entry per spot, linked by `id`:

```js
{id:'my-spot', lat:48.8462, lng:2.3461},
```

Get coordinates by right-clicking the spot in Google Maps (click the numbers to copy) or on openstreetmap.org (right-click → "Show address"/"Query features"). Google shows **latitude, longitude**; put them into `lat` and `lng` in that order. Spots missing from this list simply don't appear on the map (they still appear in the list).

### 3.6 Bonus challenges — `BONUS_ACTIONS`

Bonuses aren't tied to a place and don't appear on the map. The Paris ones (Métro, RER, Haussmann, plaques, order in French, most "Frenchy" picture) should be replaced with things that make sense locally. Each has the same fields as a location (without `cluster`).

Special case: the plaques bonus is `repeatable:true` with `maxCount` and an `options` list (a dropdown of things to find, worth `points` each up to `maxCount`). Repeatable bonuses have no separate question. To create your own "collect N things" bonus, copy that entry and edit `options`. Set `repeatable` to false (or remove it) for a normal one-off bonus.

### 3.7 Small things worth a glance

- `NAME_SUGGESTIONS` — the 🎲 random team names (currently academic jokes).
- Comment above `LOCATIONS` (the point-scale note) — update it to describe your own tiers.
- UI text is English and lives in the `render...()` functions; search for the phrase to translate it.
- The static `<title>` tag is overwritten at load by `HUNT_CONFIG.pageTitle`, so you only need to change the config.
- `participant-guide.md` and the organizer guide mention Paris specifics (start point, finish, venue notes, points); update them.

### 3.8 Running several hunts

All hunts using the same Firebase project share the same data. Either use **one Firebase project per hunt** (they're free), or reuse one project and press **Reset everything** between hunts.

---

## Running the event (quick reference)

1. **Before:** share the link; teams create their names ahead of time.
2. **Kick-off:** Admin → enter PIN → **Start the clock** (starts the countdown for every team at once).
3. **During:** keep Admin open on an organizer's phone to approve photos and answers separately; you can also leave a comment for a team at any time under History. An answer's points only count once that spot's photo is approved.
4. **At the finish:** press **Mark arrived now** next to each team as they check in; late penalties are computed automatically.
5. **After:** export whatever you need (screenshot the leaderboard), then **Reset everything**, or delete the Firebase project.

## Limits worth knowing

- **Firebase free plan:** roughly 100 simultaneous connections, 1 GB stored, 10 GB downloaded per month — plenty for one event with a handful of teams. Every phone downloads all submissions (photos included) through the live listener, so very large events (many teams, many spots each) will feel heavier; consider fewer spots or fewer teams per Firebase project.
- **One record per team per spot:** if two teammates submit the same spot at nearly the same time, the last save wins. Ask teams to designate one person to submit.
- **Duplicate team names** are allowed (each team has its own hidden ID); they just look identical on the leaderboard.
- **Photos** are compressed to 640 px JPEG before upload, so proofs are legible but not high-resolution.

## Security, honestly

The admin PIN only gates the organizer *screen*. The database rules above are open so that the app can work without accounts, which means a technically determined person could bypass the app and edit the database directly — in **either** alternative. Alternative B hides the PIN from "view source"; it doesn't make the data private. That's a sensible trade-off for a friendly two-hour game. If you need real protection, the next step is Firebase Authentication for the organizer plus database rules that only allow that account to approve, reject, or reset.