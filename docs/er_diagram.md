# Section 7 — ER Diagram Planning

---

## Overview

The ER Diagram for the Student Routine Organizer models six entities, all radiating outward from the central `USERS` entity. The diagram should be drawn using standard Chen notation or Crow's Foot notation, as preferred by the team.

---

## Entities and Their Attributes

---

### Entity: USERS

**Description:** Represents every person who has an account in the system, whether a student or admin.

**Attributes:**
- `user_id` ← **Primary Key** (underlined in ER diagram)
- `full_name`
- `email` ← Unique
- `password_hash`
- `role`
- `is_active`
- `created_at`
- `updated_at`

---

### Entity: EXERCISE_LOGS

**Description:** Represents a single exercise session recorded by a student.

**Attributes:**
- `log_id` ← **Primary Key**
- `user_id` ← **Foreign Key** → USERS
- `exercise_type`
- `duration_minutes`
- `intensity`
- `exercise_date`
- `calories_burned`
- `notes`
- `created_at`
- `updated_at`

---

### Entity: DIARY_ENTRIES

**Description:** Represents a single personal journal entry written by a student.

**Attributes:**
- `entry_id` ← **Primary Key**
- `user_id` ← **Foreign Key** → USERS
- `title`
- `content`
- `mood`
- `entry_date`
- `is_private`
- `created_at`
- `updated_at`

---

### Entity: MONEY_RECORDS

**Description:** Represents a single financial transaction (income or expense).

**Attributes:**
- `record_id` ← **Primary Key**
- `user_id` ← **Foreign Key** → USERS
- `transaction_type`
- `amount`
- `category`
- `description`
- `transaction_date`
- `notes`
- `created_at`
- `updated_at`

---

### Entity: HABITS

**Description:** Represents the definition of a habit created by a student.

**Attributes:**
- `habit_id` ← **Primary Key**
- `user_id` ← **Foreign Key** → USERS
- `habit_name`
- `description`
- `frequency`
- `start_date`
- `target_end_date`
- `is_active`
- `current_streak`
- `created_at`
- `updated_at`

---

### Entity: HABIT_LOGS

**Description:** Represents a single daily check-in event for a specific habit.

**Attributes:**
- `log_id` ← **Primary Key**
- `habit_id` ← **Foreign Key** → HABITS
- `user_id` ← **Foreign Key** → USERS
- `checkin_date`
- `notes`
- `created_at`

---

## Relationships

---

### Relationship 1: USERS → EXERCISE_LOGS

- **Type:** One-to-Many
- **Description:** One USER can have zero or many EXERCISE_LOGS. Each EXERCISE_LOG belongs to exactly one USER.
- **Crow's Foot Notation:** USERS `||—<` EXERCISE_LOGS
- **Chen Notation:** USERS (1) — logs — (N) EXERCISE_LOGS
- **Implementation:** `exercise_logs.user_id` is a Foreign Key referencing `users.user_id`.

---

### Relationship 2: USERS → DIARY_ENTRIES

- **Type:** One-to-Many
- **Description:** One USER can have zero or many DIARY_ENTRIES. Each DIARY_ENTRY belongs to exactly one USER.
- **Crow's Foot Notation:** USERS `||—<` DIARY_ENTRIES
- **Chen Notation:** USERS (1) — writes — (N) DIARY_ENTRIES
- **Implementation:** `diary_entries.user_id` is a Foreign Key referencing `users.user_id`.

---

### Relationship 3: USERS → MONEY_RECORDS

- **Type:** One-to-Many
- **Description:** One USER can have zero or many MONEY_RECORDS. Each MONEY_RECORD belongs to exactly one USER.
- **Crow's Foot Notation:** USERS `||—<` MONEY_RECORDS
- **Chen Notation:** USERS (1) — records — (N) MONEY_RECORDS
- **Implementation:** `money_records.user_id` is a Foreign Key referencing `users.user_id`.

---

### Relationship 4: USERS → HABITS

- **Type:** One-to-Many
- **Description:** One USER can define zero or many HABITS. Each HABIT is owned by exactly one USER.
- **Crow's Foot Notation:** USERS `||—<` HABITS
- **Chen Notation:** USERS (1) — defines — (N) HABITS
- **Implementation:** `habits.user_id` is a Foreign Key referencing `users.user_id`.

---

### Relationship 5: HABITS → HABIT_LOGS

- **Type:** One-to-Many
- **Description:** One HABIT can have zero or many HABIT_LOGS (one per day). Each HABIT_LOG belongs to exactly one HABIT.
- **Crow's Foot Notation:** HABITS `||—<` HABIT_LOGS
- **Chen Notation:** HABITS (1) — tracked by — (N) HABIT_LOGS
- **Implementation:** `habit_logs.habit_id` is a Foreign Key referencing `habits.habit_id`.

---

### Relationship 6: USERS → HABIT_LOGS (Auxiliary)

- **Type:** One-to-Many (denormalized reference)
- **Description:** One USER can be referenced by many HABIT_LOGS. Included for direct query performance without requiring a JOIN through HABITS.
- **Implementation:** `habit_logs.user_id` is a Foreign Key referencing `users.user_id`.

---

## ER Diagram Summary

```
USERS
  |—(1:N)—→ EXERCISE_LOGS
  |—(1:N)—→ DIARY_ENTRIES
  |—(1:N)—→ MONEY_RECORDS
  |—(1:N)—→ HABITS
                |—(1:N)—→ HABIT_LOGS
  |—(1:N)—→ HABIT_LOGS  ← (denormalized auxiliary link)
```

---

## Notes for ER Diagram Drawing

- Place **USERS** in the center of the diagram.
- Draw four branches extending outward to: EXERCISE_LOGS, DIARY_ENTRIES, MONEY_RECORDS, and HABITS.
- Draw a further branch from HABITS to HABIT_LOGS.
- Show the denormalized link from USERS directly to HABIT_LOGS as a dashed line or secondary connection.
- Mark all Primary Keys with underlining.
- Mark all Foreign Keys with a dashed underline or PK/FK notation.
- Apply Crow's Foot symbols at the "many" end of all relationships.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
