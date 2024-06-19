import pandas as pd

sort_cols = ['expert panel', 'gene symbol', 'disease entity']

def normalize_headers(df):
    df.columns = map(str.lower, df.columns)
    return df

orig = normalize_headers(pd.read_csv('./curations_export_orig.csv')).sort_values(sort_cols).reindex()
new = normalize_headers(pd.read_csv('./curations_export_new.csv').drop('ID', axis=1)).sort_values(sort_cols).reindex()
no_trashed = normalize_headers(pd.read_csv('./curations_export_new_fixed.csv').drop('ID', axis=1)).sort_values(sort_cols).reindex()


def describe_df(df):
    # print(f"columns: {df.columns}")
    # print(f"head: {df.head()}")
    print(f"rows: {len(df)}")
    print(f"duplicates: {len(df[df.duplicated()])}")
    # print(df[df.duplicated()])

# print("orig")
# describe_df(orig)

# print("\nnew")
# describe_df(new)

# print("\nno_trashed")
# describe_df(no_trashed)
# print("\n--------\n")

both = pd.concat([orig, no_trashed])
diff = both.drop_duplicates(keep=False).sort_values(sort_cols).reindex()
print(diff.loc[:, 'uploaded date':].head())

# print(f"-------\ndiff dups: {len(diff.duplicated())}")

# print(both.loc[new['gene symbol'] == 'ACTC1'])




