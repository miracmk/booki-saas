import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/appointments_provider.dart';
import 'staff_agenda_screen.dart';
import 'staff_calendar_screen.dart';
import 'staff_quick_book_screen.dart';
import 'staff_profile_screen.dart';

class StaffShell extends ConsumerWidget {
  const StaffShell({super.key});

  static const List<Widget> _screens = [
    StaffAgendaScreen(),
    StaffCalendarScreen(),
    StaffQuickBookScreen(),
    StaffProfileScreen(),
  ];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentIndex = ref.watch(staffNavIndexProvider);

    return Scaffold(
      body: IndexedStack(
        index: currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: currentIndex,
        onDestinationSelected: (idx) {
          ref.read(staffNavIndexProvider.notifier).setIndex(idx);
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.view_agenda_outlined),
            selectedIcon: Icon(Icons.view_agenda_rounded),
            label: 'Ajanda',
          ),
          NavigationDestination(
            icon: Icon(Icons.calendar_month_outlined),
            selectedIcon: Icon(Icons.calendar_month_rounded),
            label: 'Takvim',
          ),
          NavigationDestination(
            icon: Icon(Icons.add_task_rounded),
            selectedIcon: Icon(Icons.add_task_rounded),
            label: 'Hızlı Ekle',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}

