import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
import sys 
from datetime import datetime
# Use correct UTC reference
py_version = sys.version_info
print(f"Running with Python {py_version.major}.{py_version.minor}")
if py_version >= (3, 11):
    from datetime import UTC
    current_time = datetime.now(UTC)
else:
    from datetime import timezone
    current_time = datetime.now(timezone.utc)

current_month = current_time.month
print(f"Current month: {current_month}")

# Parameters of interest
parameters = ['rainfall', 'temperature', 'mintemp', 'maxtemp', 'sunhours']  # adjust as needed

# Last 7/30 day windows
window_7d = pd.read_csv("climate_recent_analysis_week.csv")
window_7d = window_7d[window_7d['type'] == 'mean']
window_30d = pd.read_csv("climate_recent_analysis_month.csv")
window_30d = window_30d[window_30d['type'] == 'mean']
longTerm = pd.read_csv("monthly_averages.csv")
current = pd.read_csv("current_weather.csv")
print(current)

# Prepare output list
output = []

# monthly longterm
longTerm = longTerm[longTerm['month'] == current_month]

# temperature 
currVal = current['temperature'].iloc[0]
print(f"Current temperature: {currVal}")

checks = {}

refVal = window_7d['temperature'].iloc[0]
checks['tmean_7d'] = currVal - refVal
refVal = window_7d['mintemp'].iloc[0]
checks['tmin_7d'] = currVal - refVal
refVal = window_7d['maxtemp'].iloc[0]
checks['tmax_7d'] = currVal - refVal

refVal = window_30d['temperature'].iloc[0]
checks['tmean_30d'] = currVal - refVal
refVal = window_30d['mintemp'].iloc[0]
checks['tmin_30d'] = currVal - refVal
refVal = window_30d['maxtemp'].iloc[0]
checks['tmax_30d'] = currVal - refVal

refVal = longTerm['temperature'].iloc[0]
checks['tmean_longTerm'] = currVal - refVal
refVal = longTerm['mintemp'].iloc[0]
checks['tmin_longTerm'] = currVal - refVal
refVal = longTerm['maxtemp'].iloc[0]
checks['tmax_longTerm'] = currVal - refVal


# rainfall
currVal = current['rainfall'].iloc[0]
refVal = window_7d['rainfall'].iloc[0]
checks['rmean_7d'] = currVal - refVal

refVal = window_30d['rainfall'].iloc[0]
checks['rmean_30d'] = currVal - refVal

refVal = longTerm['rainfall'].iloc[0]
checks['rmean_longTerm'] = currVal - refVal
refVal = longTerm['maxrain'].iloc[0]
checks['rmax_longTerm'] = currVal - refVal

# also compare 7 days to longterm
# temperature 
checks['tmean_7dlt'] = window_7d['temperature'].iloc[0] - longTerm['temperature'].iloc[0]
checks['tmin_7dlt'] = window_7d['mintemp'].iloc[0] - longTerm['mintemp'].iloc[0]
checks['tmax_7dlt'] = window_7d['maxtemp'].iloc[0] - longTerm['maxtemp'].iloc[0]

# rainfall
checks['rmean_7dlt'] = window_7d['rainfall'].iloc[0] - longTerm['rainfall'].iloc[0]


print(f"Checks: {checks}")


# Export to CSV
summary_df = pd.DataFrame(checks, index=[0])
summary_df.to_csv("climate_indicator_summary.csv", index=False)

print("comparison summary saved to climate_indicator_summary.csv")
