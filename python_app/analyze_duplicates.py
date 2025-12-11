#!/usr/bin/env python3
"""
Script untuk menganalisis dan mendeteksi duplikat dalam dataset
"""

import pandas as pd
import os
from pathlib import Path

csv_path = 'preprocessed_news.csv'

print('=' * 80)
print('📊 DATASET ANALYSIS - Duplicate Detection')
print('=' * 80)

# Load CSV
df = pd.read_csv(csv_path)

print(f'\n📋 Total Rows: {len(df)}')
print(f'📝 Columns: {list(df.columns)}')
print(f'📂 File Size: {os.path.getsize(csv_path) / (1024*1024):.2f} MB')

print('\n' + '=' * 80)
print('🔍 CHECKING FOR DUPLICATES')
print('=' * 80)

# Check duplicate by full text
print('\n1️⃣  FULL TEXT DUPLICATES (exact same content):')
duplicates_full = df[df.duplicated(subset=['text'], keep=False)]
print(f'   Total rows with exact text duplicates: {len(duplicates_full)}')
if len(duplicates_full) > 0:
    print(f'   ✓ Duplicate groups found: {len(duplicates_full.groupby("text"))}')
    print('\n   Sample duplicate texts:')
    for idx, (text, group) in enumerate(duplicates_full.groupby('text')):
        if idx >= 3:
            break
        print(f'   - Group {idx+1} ({len(group)} duplicates): "{text[:60]}..."')

# Check duplicate by processed text
if 'processed' in df.columns:
    print('\n2️⃣  PROCESSED TEXT DUPLICATES:')
    duplicates_processed = df[df.duplicated(subset=['processed'], keep=False)]
    print(f'   Total rows with duplicate processed text: {len(duplicates_processed)}')
    if len(duplicates_processed) > 0:
        print(f'   ✓ Duplicate groups found: {len(duplicates_processed.groupby("processed"))}')

# Check duplicate by title
if 'title' in df.columns:
    print('\n3️⃣  TITLE DUPLICATES:')
    duplicates_title = df[df.duplicated(subset=['title'], keep=False)]
    print(f'   Total rows with duplicate titles: {len(duplicates_title)}')
    if len(duplicates_title) > 0:
        print(f'   ✓ Unique duplicate titles: {len(duplicates_title.groupby("title"))}')
        print('\n   Sample duplicate titles:')
        title_counts = duplicates_title['title'].value_counts()
        for idx, (title, count) in enumerate(title_counts.head(5).items()):
            print(f'   - "{title[:50]}..." (appears {count} times)')

# Check null values
print('\n4️⃣  NULL VALUES:')
null_count = df.isnull().sum()
if null_count.sum() > 0:
    for col, count in null_count[null_count > 0].items():
        print(f'   - {col}: {count} null values')
else:
    print('   ✓ No null values found!')

# Statistics
print('\n' + '=' * 80)
print('📈 STATISTICS')
print('=' * 80)

if 'text' in df.columns:
    text_lengths = df['text'].str.len()
    print(f'\nText length stats (min, mean, max):')
    print(f'  - Min: {text_lengths.min()} characters')
    print(f'  - Mean: {text_lengths.mean():.0f} characters')
    print(f'  - Max: {text_lengths.max()} characters')

print(f'\nUnique values per column:')
for col in df.columns:
    unique_count = df[col].nunique()
    total_rows = len(df)
    percentage = (unique_count / total_rows) * 100
    print(f'  - {col}: {unique_count} unique ({percentage:.1f}%)')

# Category distribution if exists
if 'category' in df.columns:
    print(f'\n📂 Category Distribution:')
    category_counts = df['category'].value_counts()
    for category, count in category_counts.items():
        percentage = (count / len(df)) * 100
        print(f'  - {category}: {count} ({percentage:.1f}%)')

# Source distribution if exists
if 'source' in df.columns:
    print(f'\n📰 Source Distribution:')
    source_counts = df['source'].value_counts()
    for source, count in source_counts.items():
        percentage = (count / len(df)) * 100
        print(f'  - {source}: {count} ({percentage:.1f}%)')

print('\n' + '=' * 80)
print('✅ SUMMARY')
print('=' * 80)

total_duplicates = len(df[df.duplicated(subset=['text'], keep=False)])
duplicate_percentage = (total_duplicates / len(df)) * 100

print(f'\nTotal duplicate rows: {total_duplicates}')
print(f'Percentage of data: {duplicate_percentage:.2f}%')

if total_duplicates > 0:
    print(f'\n⚠️  RECOMMENDATION: Remove duplicates to improve data quality')
    print(f'   You can use: df.drop_duplicates(subset=["text"], keep="first")')
else:
    print(f'\n✓ No duplicates found! Dataset is clean.')

print('\n' + '=' * 80)
