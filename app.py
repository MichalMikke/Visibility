import sys
import os
import numpy as np
import matplotlib.pyplot as plt
import matplotlib as mpl
import math
import json
import shutil
import pandas as pd

def load_data(devc_path, fds_path):
    try:
        # Wczytanie pliku devc.csv za pomocą Pandas
        df_devc = pd.read_csv(devc_path, skiprows=1)
        units_line = open(devc_path, 'r').readline()
        units = [unit.strip().strip('"') for unit in units_line.strip().split(',')]

        quantities = list(df_devc.columns)
        data = df_devc.values

        # Upewnienie się, że units i quantities są tej samej długości
        if len(units) < len(quantities):
            units += [''] * (len(quantities) - len(units))
        elif len(units) > len(quantities):
            units = units[:len(quantities)]

        # Mapowanie nazw wielkości do indeksów kolumn
        quantity_indices = {quantity: idx for idx, quantity in enumerate(quantities)}

    except Exception as e:
        return False, f"Error processing devc.csv: {e}"

    try:
        with open(fds_path, 'r') as f:
            tekst = f.readlines()

        beam_dict = {}

        for t in tekst:
            if "QUANTITY='PATH OBSCURATION'" in t:
                # Pobieranie identyfikatora belki
                start = t.find("ID='") + 4
                end = t.find("'", start)
                beam_id = t[start:end]
                # Pobieranie koordynat
                S = t.find('XB=')
                cords_str = t[S+3:].rstrip().rstrip('/').split(',')
                cords_float = [float(c.strip()) for c in cords_str]
                beam_dict[beam_id] = {
                    'cords': cords_float
                }

        if not beam_dict:
            return False, "Nie znaleziono żadnych elementów BEAM w pliku fds.txt."

        # Obliczanie LP dla każdej belki
        for beam_id, beam_info in beam_dict.items():
            cords = beam_info['cords']
            X = cords[1] - cords[0]
            Y = cords[3] - cords[2]
            Z = cords[5] - cords[4]
            LP_val = math.sqrt(X**2 + Y**2 + Z**2)
            beam_info['LP'] = LP_val

        # Sortowanie belek
        sorted_beams = sorted(beam_dict.keys())
        num_beams = len(sorted_beams)

        # Obliczanie LE jako odległości do następnej belki
        for idx, beam_id in enumerate(sorted_beams):
            beam_info = beam_dict[beam_id]
            curr_cords = beam_info['cords']
            curr_center_x = (curr_cords[0] + curr_cords[1]) / 2
            curr_center_y = (curr_cords[2] + curr_cords[3]) / 2

            if idx < num_beams - 1:
                # Odległość do następnej belki
                next_beam_id = sorted_beams[idx + 1]
                next_cords = beam_dict[next_beam_id]['cords']
                next_center_x = (next_cords[0] + next_cords[1]) / 2
                next_center_y = (next_cords[2] + next_cords[3]) / 2

                X_next = next_center_x - curr_center_x
                Y_next = next_center_y - curr_center_y
                LE_val = math.sqrt(X_next**2 + Y_next**2)
            else:
                # Dla ostatniej belki ustawiamy LE jako wartość LE poprzedniej belki lub domyślną wartość
                if idx > 0:
                    LE_val = beam_dict[sorted_beams[idx - 1]]['LE']
                else:
                    LE_val = 1.0  # Domyślna wartość, jeśli jest tylko jedna belka

            beam_info['LE'] = LE_val

        # Mapowanie identyfikatorów belek na indeksy kolumn w data
        beam_indices = {}
        for beam_id in beam_dict.keys():
            if beam_id in quantities:
                idx = quantities.index(beam_id)
                beam_indices[beam_id] = idx
            else:
                for idx, quantity in enumerate(quantities):
                    if beam_id.strip().lower() == quantity.strip().lower():
                        beam_indices[beam_id] = idx
                        break
                else:
                    print(f"Beam ID '{beam_id}' nie znaleziony w data.")
                    # Możesz zdecydować się na pominięcie lub zgłoszenie błędu

    except Exception as e:
        return False, f"Error processing fds.txt: {e}"

    return True, {
        'data': data,
        'units': units,
        'quantities': quantities,
        'beam_data': beam_dict,
        'beam_indices': beam_indices,
    }

def preprocess_files(devc_path, fds_path):
    success, data_dict = load_data(devc_path, fds_path)
    if not success:
        return False, data_dict

    beams = list(data_dict['beam_data'].keys())

    if not beams:
        return False, "Nie znaleziono żadnych elementów BEAM w pliku fds.txt."

    return True, {'beams': beams}

def process_files(devc_path, fds_path):
    success, data_dict = load_data(devc_path, fds_path)
    if not success:
        return False, data_dict

    data = data_dict['data']
    units = data_dict['units']
    quantities = data_dict['quantities']

    # Znalezienie indeksów kolumn z jednostką '%/m'
    alarm_devices_indices = []
    for i, unit in enumerate(units):
        if unit == "%/m":
            alarm_devices_indices.append(i)

    if not alarm_devices_indices:
        return False, "Nie znaleziono żadnych urządzeń z jednostką '%/m' do wyznaczenia czasu alarmu."

    # Pobranie danych dla urządzeń alarmowych
    alarm_devices_data = data[:, alarm_devices_indices]

    # Wartość progu
    threshold = 10

    # Tablica logiczna gdzie True oznacza przekroczenie progu
    exceeds_threshold = alarm_devices_data >= threshold

    # Sumowanie przekroczeń progu w czasie
    exceeds_count = np.sum(exceeds_threshold, axis=1)

    # Znalezienie najwcześniejszego czasu, gdy co najmniej dwa urządzenia przekroczyły próg
    indices = np.where(exceeds_count >= 2)[0]

    if indices.size > 0:
        alarm_time = data[indices[0], 0]
    else:
        return False, "Nie znaleziono czasu alarmu spełniającego kryteria."

    return True, alarm_time

def clear_results_folder(results_folder):
    for filename in os.listdir(results_folder):
        file_path = os.path.join(results_folder, filename)
        if os.path.isfile(file_path):
            os.unlink(file_path)

def generate_plots(
    alarm_time,
    data,
    units,
    quantities,
    beam_data,
    beam_indices,
    alarm_devices_indices,
    ordered_beams,
    results_folder
):
    try:
        fig_size = (12, 8)
        dpi = 100
        result_files = []

        # Generowanie wykresów dla każdej kolumny
        for i in range(1, len(quantities)):
            fig, ax = plt.subplots(figsize=fig_size, dpi=dpi)
            plt.xlabel("Czas [s]")
            plt.ylabel(f"Wartość [{units[i]}]")
            ax.plot(data[:, 0], data[:, i], label=quantities[i])
            ax.legend()
            ax.get_xaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())
            ax.get_yaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())
            filename = f"devc_{i:02}_{quantities[i]}.png"
            filepath = os.path.join(results_folder, filename)
            fig.savefig(filepath)
            result_files.append(filename)
            plt.close(fig)

        # Generowanie wykresu widoczności dla wybranych BEAM
        color_map = plt.get_cmap('viridis')
        ccount = 0
        fig, ax = plt.subplots(figsize=fig_size, dpi=dpi)
        plt.xlabel("Czas [s]")
        plt.ylabel("Widoczność [m]")

        ts = 0
        plotted_beams = set()
        num_beams = len(ordered_beams)

        for beam_id in ordered_beams:
            if beam_id in plotted_beams:
                continue

            i = beam_indices.get(beam_id)
            if i is None:
                print(f"Beam ID '{beam_id}' nie znaleziony w data. Pomijanie.")
                continue

            try:
                b_data = beam_data[beam_id]
            except KeyError:
                return False, f"Error generating plots: '{beam_id}' is not in beams list"

            LP_b = b_data['LP']
            LE_b = b_data['LE']

            a = len(data[:, i])
            R = 30  # Visibility Limit from FDS Manual [m]
            K = 3  # Shading Factor
            MY = []
            for j in range(0, a):
                if data[j, i] > 0:
                    l = data[j, i]
                    if l < 100:
                        l = (1 - (l / 100))
                        try:
                            l = np.log(l)
                        except ValueError:
                            l = -np.inf
                        if l == -np.inf:
                            y = R
                        else:
                            y = -((K * LP_b) / l)
                            if y >= R:
                                y = R
                            if y <= -R:
                                y = R
                    else:
                        y = 0
                    MY.append(y)
                else:
                    MY.append(R)
            # Obsługa nieskończoności lub NaN
            MY = [y if np.isfinite(y) else R for y in MY]

            color = color_map(ccount / max(1, num_beams - 1))

            # Ustawienie minimalnej szerokości słupka
            bar_width = LE_b / 1.2
            if bar_width <= 0:
                bar_width = 1.0  # Minimalna szerokość słupka

            LN = f"{LP_b:.1f}"  # Zmiana precyzji na jedno miejsce po przecinku
            ax.plot(data[:, 0], MY[:], label=beam_id + " Widoczność [m]",
                    color=color)
            ax.stackplot(data[:, 0], MY[:], alpha=0.1, colors=[color])

            ax.bar(alarm_time + ts, LP_b, bar_width, align='edge',
                   label=beam_id + " Długość przejścia " + LN + " [m]",
                   color=color)
            ts += bar_width
            ccount += 1

            plotted_beams.add(beam_id)

        if ccount == 0:
            return False, "Żaden z wybranych BEAM nie został znaleziony w danych."

        ax.legend()
        ax.get_xaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())
        ax.get_yaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())

        visibility_filepath = os.path.join(results_folder, "visibility.png")
        fig.savefig(visibility_filepath)
        result_files.append("visibility.png")

        plt.xlim(0, alarm_time + ts + 30)
        visibility_cut_filepath = os.path.join(results_folder, "visibility_cut.png")
        fig.savefig(visibility_cut_filepath)
        result_files.append("visibility_cut.png")
        plt.close(fig)

        # Generowanie wykresu dla urządzeń z jednostką '%/m'
        if alarm_devices_indices:
            fig, ax = plt.subplots(figsize=fig_size, dpi=dpi)
            plt.xlabel("Czas [s]")
            plt.ylabel("Wartość [%/m]")
            for idx in alarm_devices_indices:
                ax.plot(data[:, 0], data[:, idx], label=quantities[idx])
            ax.legend()
            ax.get_xaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())
            ax.get_yaxis().set_minor_locator(mpl.ticker.AutoMinorLocator())
            alarm_devices_chart_filepath = os.path.join(results_folder, "alarm_devices.png")
            fig.savefig(alarm_devices_chart_filepath)
            result_files.append("alarm_devices.png")
            plt.close(fig)

        return True, result_files

    except Exception as e:
        return False, f"Error generating plots: {e}"

if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description='Process files and generate plots.')
    parser.add_argument('action', choices=['preprocess', 'process', 'generate'], help='Action to perform')
    parser.add_argument('--devc', help='Path to devc.csv file')
    parser.add_argument('--fds', help='Path to fds.txt file')
    parser.add_argument('--alarm_time', type=float, help='Alarm time')
    parser.add_argument('--ordered_beams', help='Comma-separated list of ordered beams')
    parser.add_argument('--results_folder', default='results', help='Folder to save results')

    args = parser.parse_args()

    if args.action == 'preprocess':
        if not args.devc or not args.fds:
            print(json.dumps({'success': False, 'message': 'Brak plików devc.csv lub fds.txt'}))
            sys.exit(1)
        success, data = preprocess_files(args.devc, args.fds)
        if success:
            print(json.dumps({'success': True, 'beams': data['beams']}))
        else:
            print(json.dumps({'success': False, 'message': data}))
    elif args.action == 'process':
        if not args.devc or not args.fds:
            print(json.dumps({'success': False, 'message': 'Brak plików devc.csv lub fds.txt'}))
            sys.exit(1)
        success, alarm_time = process_files(args.devc, args.fds)
        if success:
            print(json.dumps({'success': True, 'alarm_time': alarm_time}))
        else:
            print(json.dumps({'success': False, 'message': alarm_time}))
    elif args.action == 'generate':
        if not args.devc or not args.fds or args.alarm_time is None or not args.ordered_beams:
            print(json.dumps({'success': False, 'message': 'Brak wymaganych argumentów'}))
            sys.exit(1)
        ordered_beams = args.ordered_beams.split(',')
        success, data_dict = load_data(args.devc, args.fds)
        if not success:
            print(json.dumps({'success': False, 'message': data_dict}))
            sys.exit(1)

        data = data_dict['data']
        units = data_dict['units']
        quantities = data_dict['quantities']
        beam_data = data_dict['beam_data']
        beam_indices = data_dict['beam_indices']

        # Zbieranie indeksów urządzeń z jednostką '%/m'
        alarm_devices_indices = []
        for i, unit in enumerate(units):
            if unit == "%/m":
                alarm_devices_indices.append(i)

        # Czyszczenie folderu wyników
        os.makedirs(args.results_folder, exist_ok=True)
        clear_results_folder(args.results_folder)

        success, result_files = generate_plots(
            args.alarm_time,
            data,
            units,
            quantities,
            beam_data,
            beam_indices,
            alarm_devices_indices,
            ordered_beams,
            args.results_folder
        )

        if success:
            print(json.dumps({'success': True, 'result_files': result_files}))
        else:
            print(json.dumps({'success': False, 'message': result_files}))
