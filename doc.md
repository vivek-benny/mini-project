CELESTIA INTERIOR & EXTERIOR DESIGNS

PROJECT REPORT SUBMITTED IN PARTIAL FULFILMENT OF THE
REQUIREMENTS FOR THE AWARD OF
BACHELOR OF COMPUTER APPLICATIONS

To

MARIAN COLLEGE KUTTIKKANAM [AUTONOMOUS]

Affiliated to

MAHATMA GANDHI UNIVERSITY, KOTTAYAM

By
SHEETHAL S
(Reg.No:22UBC257)
DERIN JACOB BABU
(Reg.No:22UBC222)
Guided By
Ms. SHEELA S
DEPARTMENT OF COMPUTER APPLICATIONS
MARIAN COLLEGE KUTTIKKANAM (AUTONOMOUS)
PEERMADE – 685531

OCTOBER, 202 4

DECLARATION
We, SHEETHAL S [Reg.no 22UBC257], DERIN JACOB BABU
[Reg.no 22UBC222] certify that the Mini project report entitled “CELESTIA INTERIOR &
EXTERIOR DESIGNS” is an authentic work carried out by us at Marian College
Kuttikkanam(Autonomous). The matter embodied in this project work has not been submitted
elsewhere for the award of any degree or diploma to the best of our knowledge and belief.

Signature of the Students:

SHEETHAL S

DERIN JACOB BABU

Date:

BONAFIDE CERTIFICATE
This is to certify that this project work entitled “CELESTIA
INTERIOR & EXTERIOR DESIGNS” is a bonafide record of work done by Ms. SHEETHAL
S [Reg.no 22UBC257], Mr. DERIN JACOB BABU [Reg.no 22UBC222] at Marian College
Kuttikkanam (Autonomous) in partial fulfilment for the award of the Degree of Bachelor of
Computer Applications of Mahatma Gandhi University, Kottayam.

This work has not been submitted elsewhere for the award of any degree to the best of our
knowledge.

Head of the Department Internal Guide

Dr Rajimol A Ms. Sheela S

Dept. of Computer Applications Dept. of Computer Applications

Marian College Kuttikkanam Marian College Kuttikkanam

Peermade – 685531 Peermade - 685531

Submitted for the Viva-Voice Examination held on

DEPARTMENT SEAL
EXTERNAL EXAMINER
ACKNOWLEDGEMENT
“Gratitude is a feeling which is more eloquent than words,
more silent than silence.” In undertaking this project, we needed the direction, assistance and
cooperation of various individuals and organizations, which is received in abundance with
grace of God, without their unconstrained support, the project could not have been
completed. If words are considered as the symbol of approval and token of
acknowledgement, then let the following words play the heralding role of expressing our
gratitude.
We wish to acknowledge our sincere gratitude to our Manager, Rev.Fr. Boby Alex
Mannamplackal and Dr Ajimon George, Principal Marian College Kuttikkanam
(Autonomous), for all their efforts and administration in educating us in their premier
institution.
We extend our gratitude to Dr Rajimol A, Head of the Department of Computer Application,
who is a constant source of inspiration and whose advice helped us to complete this project
successfully.We express our deep sense of gratitude to our internal project guide, Ms Sheela
S, for her profound guidance for the successful completion of this project. With great
enthusiasm we express our gratitude to all the faculty members of Department of Computer
Applications for their timely help and support.
Finally, we express our deep appreciation to all our friends and family members for the
moral support and encouragement they have given to complete this project successfully.

Derin Jacob Babu, Sheethal S
ABSTRACT

ABSTRACT
The project named “Celestia Interior and Exterior Design” is a web-
based platform for discovering, planning, and executing design projects. Celestia's streamlined
processes make designing and decorating spaces more accessible and convenient, offering
essential tools for both users and design professionals in the digital era. The system mainly
consists of two modules: Users and Admins. Users can explore trendy design ideas, purchase
products, and hire professionals. They can view detailed design information, book virtual or in-
person consultations with design experts.The platform also allows users to track their purchase
history for easy reference.

Admins can oversee consultations, and manage website content and payments. Admins have the
ability to ensure that consultations are scheduled efficiently. Additionally, the platform supports
secure payment processing and real-time notifications to keep users informed about their bookings
and purchases.

The platform uses HTML, CSS, and JavaScript for the front end, providing an intuitive user
interface. The back end, developed with PHP and MySQL, ensures robust data management and
seamless functionality.

TABLE OF CONTENTS
1.INTRODUCTION TITLE .PAGE NUMBER
1.1 ABOUT THE PROJECT
1.2 EXISTING SYSTEM
1.3 PROPOSED SYSTEM
SYSTEM ANALYSIS
2.1 PROBLEM DEFINITION
2.2 FEASIBILITY ANALYSIS
2.3 RECOMMENDED IMPLEMENTATIONS
SOFTWARE REQUIREMENT SPECIFICATION
3.1 INTRODUCTION
3.2 PURPOSE
3.3 SCOPE
3.4 TECHNICAL OVERVIEW
3.5 FUNCTIONAL REQUIREMENTS
3.6 NON-FUNCTIONAL REQUIREMENTS
3.7 STATED REQUIREMENTS
3.8 EXTERNAL INTERFACE REQUIREMENTS
SYSTEM DESIGN
4.1 INTRODUCTION
4.2 DESIGN METHODOLOGY
4.3 SYSTEM ARCHITECTURE AND PROCESS FLOW
4 .4 MODULE DETAILS
4.5 PERFORMANCE CONSIDERATIONS
4.6 SECURITY CONSIDERATIONS
4.7 TABLE DESIGN
5.CODING
5.1 INTRODUCTION
5.2 SELECTION OF SOFTWARE
5.3 CODING PHASE
TESTING
6.1 INTRODUCTION
6.2 TESTING
6.3 TESTING METHODS
6.4 IMPLEMENTATION
MAINTENANCE AND ENHANCEMENT
7.1 MAINTENANCE
7.2 ENHANCEMENT
CONCLUSION
INTRODUCTION

1.INTRODUCTION
1.1 ABOUT THE PROJECT
The Celestia Interior & Exterior Design Website is a digital platform tailored to connect users
with interior and exterior design solutions. It provides a comprehensive suite of features for users
to explore trendy design ideas, purchase products and book consultations with design experts. The
website's user-friendly interface ensures a smooth navigation experience, allowing users to easily
view detailed design information, and connect with design professionals for inquiries and support.

1.1.1 THE PURPOSE AND SCOPE

The primary purpose of the Celestia Interior & Exterior Design Website Project is to establish an
efficient and convenient online platform for interior and exterior design solutions. The project
aims to provide a centralized hub where users can discover design ideas, purchase products, book
consultations, and interact with design professionals seamlessly. The scope of the project includes:

➢ Enabling users to explore and filter design ideas.
➢ Empowering users to purchase products directly from the platform with detailed product
information, including images and descriptions..
➢ Facilitating user inquiries and support through a contact form.
➢ Implementing admin functionalities to manage product listings, consultations, customer
support.
1.2 EXISTING SYSTEM
In the current scenario, accessing interior and exterior design solutions often involves fragmented
and time-consuming methods. Users typically rely on physical interactions, limited design
catalogs, and scattered communication channels with design professionals. This manual approach
can lead to inefficiencies, delays, and limited access to design resources.

1.3 PROPOSED SYSTEM
The main objective of the proposed Celestia Interior & Exterior Design Website is to eliminate
the limitations of the existing manual system and make it more user-friendly. Most of the

limitations of the current system can be overcome by the proposed system. Speed and accuracy
are the main advantages of the proposed system, with no redundancy of data. The proposed
software requires fewer resources to manage the entire design platform. Since all details are stored
digitally, searching time is reduced. Information can be more secure because computer systems
provide better data security. The proposed system eliminates many drawbacks of the existing

system and enhances data security, user experience, and operational efficiency.

SYSTEM ANALYSIS

2. SYSTEM ANALYSIS
2.1 PROBLEM DEFINITION
The current system does use digital solutions for interior and exterior design needs, but it lacks
refinement. Even when records are kept digitally, they are often managed inefficiently and are not
integrated with the internet. This makes it difficult to handle various aspects like user interactions,
product management, and consultations efficiently. Much of the record-keeping and scheduling
are still done manually, leading to errors and delays. To overcome these limitations, the Celestia
Interior & Exterior Design Website is proposed.

2.1.1 ADVANTAGES OF PROPOSED SYSTEM

➢ Avoids Redundancy: The system uses various types of validation to eliminate
redundancy, enhancing overall efficiency.
➢ Quick Access and Processing: The proposed system offers faster access and processing,
making it more efficient than the existing system.
➢ Time Efficiency: The new automated system significantly reduces time consumption
compared to the existing manual system.
➢ Reduces Paperwork: The system minimizes the need for paperwork, streamlining
operations.
➢ Provides Accurate Information: The system ensures the accuracy of information,
improving reliability and decision-making.
2.2 FEASIBILITY ANALYSIS
Feasibility study is a test of a system proposal according to its workability, ability to meet user
needs and effective use of resources. The objective of feasibility is not to solve the problem but
to acquire a sense of its scope. The main aim of the feasibility study is to test the technical, social
and economic feasibility of the system. The feasibility study can be classified into the following
categories:

➢ Operational Feasibility
➢ Technical Feasibility
➢ Economic Feasibility
2.2.1 OPERATIONAL FEASIBILITY

The proposed system offers user friendliness combined with greater processing speed. Therefore,
the work done can be reduced. Since the processing speed is very high compared with manual
system a lot of time can be saved. The workload is reduced and this system requires only a small
amount of work from Admin who manages the whole system. Hence, this project is operationally
feasible.

2.2.2 TECHNICAL FEASIBILITY

Technical feasibility deals with hardware as well as software requirements and to what extend it
can support the proposed system. The hardware required is an android phone and software is
Android Studio. If the necessary requirements are made available with the system, then the
proposed system is said to be technically feasible.

2.2.3 ECONOMIC FEASIBILITY

Economic feasibility is an important factor. Since the existing system is manual on the feasibility
for wrong data entry is higher and consumes a lot of time and can occur errors. But the proposed
system aims at processing of information’s efficiently, thus saving the time. The new system need
only a system and which is already available therefore the cost is negligible. Proposed system
uses validation check so there are no errors. Even though an initial investment has to be made on
the software and the hardware aspects, the proposed system aims at processing of information’s
efficiently. Thus, the benefits acquired out of the system are sufficient enough for the project to
be undertaken. So, the proposed system is economically feasible.

2.3 RECOMMENDED IMPLEMENTATIONS
Two principle sources of data are:

➢ Written documents
➢ Data from the persons, who are involved in the operation of the system under study.
The different fact-finding techniques are:

➢ Questionnaires
➢ Personal Interviews
➢ Observations
Questionnaires

Questionnaires are best methods to probe data out of the customers. In this case, questionnaires
were not used for data collection as the administration was small in number and they could be
asked questions in a more effective interview.

Personal Interviews

Personal interviews are the best way to gather facts. This was the primary source of fact finding
used for this project. The owner and the employees were interviewed and data collected. They
were asked how the administrative duties and record keeping happened under the existing system.
And suggestions were taken on what they wanted to add in addition in this system. Almost all
their suggestions were integrated into this project.

Observations

A person can understand a lot about a system just by observing it. By being a bystander and
observing how a day passes in the real estate helped to kick off this project. Using this method
resulted in a better understanding of the workings of the organisation and what to do to make this
an web application. Observing the current system, one can understand that a lot of paperwork and
staff services involved in the administrative and distribution area. The way in which the records
are kept gives an idea for a strong database model.

SOFTWARE

REQUIREMENT

SPECIFICATIONS

3. SOFTWARE REQUIREMENT SPECIFICATION
3.1 INTRODUCTION
Requirements specification is the starting step for the development activities. It is currently one
of the weak areas of software engineering. During requirement specification, the goal is to
produce a document of the client’s requirements. This document forms the basis of
development and software validation. The basic reason for the difficulty in software
requirements specification comes from the fact that there are three interested parties- the client,
the end users and the software developer.

3.2 PURPOSE
The origin of most software systems is in the need of a client, who either wants to automate an
existing manual system or desires a new software system. The software system itself is created
by the developer. Finally, the completed system will be used by the end users. Thus, there are
three major parties interested in a new system: the client, the users and the developer. A basic
purpose of software requirements specification is to bridge the communication gap. SRS is the
medium through which the client and user needs are accurately specified. Indeed, SRS forms
the basis of software development. A good SRS should satisfy all the parties, something very
hard to achieve, and involves trade-offs and persuasion.
Another important purpose of developing an SRS is helping the clients understand their
own needs. Advantages are:

An SRS establishes the basis for agreement between the client and the supplier on what
the software product will do
An SRS provides a reference for validation of the final product
A high-quality SRS is a prerequisite to high-quality software.
A high-quality SRS reduces the development cost.
3.3 SCOPE
3.3.1 SYSTEM STATEMENT OF SCOPE

The Celestia Interior & Exterior Design Website was developed to provide a platform for
discovering, planning, and executing interior and exterior design projects. The system further
offers features such as purchasing products, booking consultations, and saving favorite designs.
Reports can be generated to retrieve data.

3.4 TECHNICAL OVERVIEW
3.4.1 USER CHARACTERISTICS

The system can be accessed by two types of users: Admin and User. Admins have access only to
the admin dashboard, while users have access to the site and all its services. The admin
functionalities are invisible to users.
3.5 FUNCTIONAL REQUIREMENTS
The functional requirements of this website are as follows:

User Management:
User Registration Process:
Users provide essential details like name, email, and password.
User Authentication:
➢ Users log in by entering their registered email and password.
➢ The system verifies credentials against stored data to allow access.
➢ Users can securely log out, ending their active sessions to prevent unauthorized
access.
Design Idea Management:
➢ Users can explore and view trendy design ideas.
➢ Users can purchase design-related products directly from the platform.

Consultation Management:
➢ Users can book virtual or in-person consultations with design experts.
➢ Admins can manage and oversee consultation bookings, ensuring efficient scheduling.

Product Management:
➢ Admins can add new design-related products, edit existing product information, and
remove items as needed.
➢ Admins can upload, edit, or remove product images to ensure accurate representation.

Cart Management:
➢ Users can add or remove products from their cart.
➢ The cart automatically updates quantities and total prices.
➢ Users can view a summary of their cart, including item details and total cost.

Order Management:
➢ The system collects all billing information during checkout.
➢ Essential order details, such as product information, quantity, and customer details, are
stored.

Purchase History:
➢ Users can view their purchase history, including product details, quantities, prices, and
order dates.
➢ Users can track their past orders conveniently.

Admin Dashboard:
➢ Admins can oversee consultations.
➢ Admins can manage website content, payments, before publication.
➢ Admins can monitor and ensure efficient consultation scheduling.

3.6 NON-FUNCTIONAL REQUIREMENTS
The non-functional requirements of this website are as follows:

Usability:
➢ Intuitive and user-friendly interface.
➢ Comprehensive design features and clear navigation.
Reliability:
➢ System performs reliably in 95% of use cases monthly.
➢ Ensure consistent and accurate data processing.
Availability:
➢ Accessible globally 24/7.
➢ Backup and recovery mechanisms to restore service within an hour of a failure.
Security:
➢ Hourly backups of the database.
➢ Secure handling of user data to protect privacy and prevent unauthorized access.
Performance:
➢ Quick response times and minimal latency.
➢ Efficient handling of large volumes of data.
Scalability:
➢ Capable of supporting a growing number of users and transactions.
➢ Modular architecture to facilitate easy expansion.
Maintainability:
➢ Codebase should be well-documented and modular for ease of updates and bug fixes.
➢ Regular maintenance schedules to ensure system health and performance.
3.7 STATED REQUIREMENTS
7 .1 GENERAL REQUIREMENTS
The system has 7 functional modules divided between admin and users.

Login
➢ Only registered users and admins can log in to the system to avail the services.
➢ The registered users and admins use their ID and password to log in.
➢ The user ID should always be a valid email ID.
➢ The password can contain both uppercase and lowercase alphabetic characters,
numbers, and special characters.
➢ Admins will be redirected to the admin panel when logging in with the predefined ID
and password using the same login form.
➢ Users will be directed to the home page of the site when the login is successful.
Sign Up
➢ New users need to register in order to use the platform's services.
➢ This includes several fields:
▪ First Name
▪ Last Name
▪ Email Address
▪ Phone Number
▪ Password
▪ Confirm Password
Admin Panel
The admin panel contains different admin processes such as:
▪ Overseeing consultations
▪ Managing website content
▪ Managing payments
Explore Design Ideas
Users can browse through various design ideas and inspirations.
Purchase Products
Users can buy products listed on the platform.
Booking Services
➢ Users can book various services offered on the platform.
➢ Users can manage their bookings and view booking details.
Booking Consultations
➢ Users can schedule consultations with design experts.
➢ Users can manage their consultation appointments.
7 .2 INPUTS
The Celestia platform will take various inputs to provide its services:
User Registration:
➢ First Name, Last Name
➢ Email Address
➢ Phone Number
➢ Password, Confirm Password
Login:
➢ Email Address (User ID)
➢ Password
Service Booking:
➢ Selected Service
➢ Preferred Booking Time
Consultation Booking:
➢ Consultation Type (Virtual/In-person)
➢ Preferred Consultation Time
7 .3 PROCESSING
The system will perform the following key processing tasks:

Data Validation:
➢ Validate email format and password complexity.
➢ Ensure required fields are not empty.
Service and Consultation Management:
➢ Users book and manage services and consultations.
➢ Admins oversee bookings and consultations.
Content Management:
Admins update and manage website content.
E-commerce:
➢ Users add products to cart, place orders, and track order history.
➢ Secure payment processing.
7 .4 OUTPUTS
The system produces the following outputs:

Product and Design Details:
➢ Product Name, Description
➢ Design Ideas
Booking and Consultation Details:
Confirmed booking and consultation details
Order and Payment Confirmation:
Order details, payment confirmation.
3.8 EXTERNAL INTERFACE REQUIREMENTS
8 .1 USER INTERFACES
All user interfaces will be GUI interfaces, designed for ease of use and high functionality. The
interfaces will have a pleasing appearance and intuitive design.

Design and Appearance:
➢ The interface will use suitable design elements and pleasing colors to create a
comfortable and attractive environment for users.
➢ Consistent design themes will be maintained across all pages.
Usability Components:
➢ Textboxes, combo boxes, and buttons will be used to facilitate easy data entry.
➢ Clear labels and instructions will be provided for all input fields.
➢ Intuitive navigation elements will help users move seamlessly through the
platform.
Responsive Design:
The user interface will be responsive to ensure usability across various devices,
including desktops, tablets, and smartphones.
8 .2 HARDWARE INTERFACES
The system needs a computer or any other smart devices with network availability to access the
web application. No other external hardware is required.

Hardware Specification

Processor: Intel Pentium or higher
RAM: 256 MB or higher
Hard Disk Drive: 100 MB required on disk
Keyboard: Standard QWERTY keyboard
Implementation Specification

Operating System: Windows OS
8 .3 SOFTWARE INTERFACES
Software Specification

Operating System: Windows 11
DBMS: MySQL
Tool Used: PHP
SYSTEM DESIGN

4. SYSTEM DESIGN
4.1 INTRODUCTION
The design phase aims to develop a solution for the problems identified during the analysis phase.
This phase marks the transition from understanding the problem to creating a solution. System
design details the required features and operations, including screen layouts, business rules,
process diagrams, pseudocode, and other relevant documentation.

During this phase, the overall structure and specifics of the software are defined. This includes
determining the number of tiers needed for the architecture, designing inputs and outputs, and
establishing database and data structure designs. Proper analysis and design are critical to the
development cycle since errors in this phase can be costly to fix later. Therefore, careful attention
is given during the design phase.

The logical framework of the product and its physical attributes are outlined during this stage.
The operating environment is set up, and key resources are identified. Any element that requires
user input or approval must be documented and reviewed by the user. The physical aspects of the
system are specified, and a detailed design is prepared.

Subsystems identified during design are used to create a detailed system structure. Each subsystem
is divided into one or more design units or modules, and detailed logic specifications are created
for each module. The module logic is typically described in a high-level design language, which
is independent of the final implementation language.

A good design should consider:

➢ Promptness: The design should be straightforward and clear, guiding users to their
desired outcomes intuitively.
➢ Memory Load: Research shows that users can retain about six words in their short-term
memory. The number of choices presented to users should ideally be four or fewer to
prevent confusion and forgetfulness.
➢ Service Reachability: Users dislike going through many steps to access a service. More
than five steps can cause impatience, so minimizing the number of steps helps reduce
frustration.
➢ Navigation: Users should easily navigate back and forth between different steps, allowing
them to access various parts of the dialog seamlessly.
➢ Phonetic Similarity: Avoid choices with similar pronunciations to reduce confusion and
ensure users can clearly distinguish between options.
➢ Error Handling: Implementing graceful error handling helps decrease dependency on
operators by managing mistakes effectively.
➢ User Updates: Keep users informed about the ongoing process to maintain their
engagement and understanding.
For the general design, one or more potential designs are proposed and broadly sketched. These
alternatives are then presented to the users, who choose the design that best suits their
requirements while staying within project constraints.

The detailed design stage specifies the user interface, database, programs, hardware, and training
and system documentation. Several structured techniques are used during the design phase. To
design the software components, the designer transforms the automated processes in the physical
data flow diagram into a program structure chart, which decomposes software processes into
detailed modules and shows control paths between modules.

4.2 DESIGN METHODOLOGY
4.2.1 INPUT DESIGN

Input design focuses on converting user-oriented inputs into a format recognizable by the
computer. Collecting input data is one of the most costly parts of the system in terms of equipment,
time, and user involvement. The goal of input design is to make data entry as simple, logical, and
error-free as possible.

Input design serves as the bridge between the information system and its users, transforming
transaction data into a form suitable for processing. This process can involve reading data from
printed documents or directly keying data into the system. Effective input design controls the
amount of input required, minimizes errors, avoids delays and extra steps, and keeps the process
straightforward.

System analysis determines the following input design details:

➢ What data to input
➢ What medium to use
➢ How the data is arranged and coded
➢ Data items and transactions requiring validation to detect errors
Activities involved in input design include:

➢ Data Recording: Collecting data for input.
➢ Data Verification: Ensuring the accuracy of the collected data.
➢ Data Conversion: Transforming data into the required format.
➢ Data Validation: Checking data for errors.
➢ Data Correction: Fixing any identified errors.
4.2.1 OUTPUT DESIGN

Output design involves creating necessary outputs tailored to meet the requirements of various
users. It is essential to approach the design of computer outputs in a well-considered manner.
Outputs refer to any information generated by the information system, whether in printed or
displayed form. Analysts design computer outputs by identifying specific outputs required to
fulfill system requirements.

Computers serve as crucial sources of information for users. Efficient and thoughtful output
design enhances the system's interaction with users and supports decision-making processes.
When designing outputs, system analysts must achieve the following objectives:

Determine the information to be presented
Decide whether to display, print, or verbally communicate the information, and select the
appropriate output medium
Format the information in an easily understandable manner
Output design is critical to the success of any system as it bridges the gap between the user and
the system's operations. Effective output design includes specifications and procedures for
presenting data clearly to users. Users should never be left uncertain about system activities, as
appropriate error messages and acknowledgment messages are provided.

4.2.3 CODE DESIGN

The coding phase transforms the detailed software design into a programming language. It
translates the software's detailed design representation into executable code. Code design aims to
minimize the lines of code used while modularizing the implementation. Modules hide complexity
by encapsulating executable statements under named functions or procedures. Effective
information hiding enhances program understanding at higher abstraction levels. Module names
should accurately describe their actions to avoid confusion.

In this software, a modularized approach is employed with different functions created for various
operations, each named to reflect its action.

4.2.4 DATABASE DESIGN

Database design identifies relevant data relationships and defines tables using standard methods.
Each table's attributes are carefully defined to optimize database performance, ensure data
integrity, minimize redundancy, and enhance security.

A database system is a computer representation of an information system designed to handle
integrated data efficiently. It minimizes redundancy to provide quick, flexible, and cost-effective
information access. Database design considers several specific objectives:

➢ Controlled redundancy
➢ User-friendly interface
➢ Data independence
➢ Cost-effective data retrieval
➢ Accuracy and integrity
➢ Failure recovery
➢ Privacy and security
➢ Performance optimization
Database design involves creating multiple views of data, including logical and physical views.
The logical view represents data independently of its storage, focusing on how users and
programmers interact with it. The physical view describes how data are stored and accessed in
physical storage.

Each table in the database typically includes a primary key, a unique column (or combination of
columns) that uniquely identifies each record. Primary keys ensure data integrity by enforcing
uniqueness and cannot contain null values.

Normalization is employed to organize database data, minimizing redundancy and anomalies
during data insertion, update, and deletion operations.

4.3 SYSTEM ARCHITECTURE AND PROCESS FLOW
UML DIAGRAMS
4.3.1 USE CASES

REGISTRATION

Use Case Id: CL_UC_01
Use Case Name: Registration
Created by: Derin & Sheethal
Date Created: 20 - 07 - 2024
Description: Allows new users to register for an
account on
the platform.
Primary actor: User
Secondary actor: None
Precondition: User navigates to the registration page
Postcondition: User account is successfully created.
Main flow: 1. User navigates to the
registration page.
User provides required
details: first name,last
name, email, phone no,
and password.
User submits the registration
form.
System validates the
information and creates the
user account.
Use case ends.
LOGIN

Use Case Id: CL_UC_02
Use Case Name: Login
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case enables users to access
the system by entering their credentials.
There are two user roles: admin and
customer. The admin logs in using their
admin email and password, while the
customer uses their own email and
password. Once logged in, customers
can purchase, and admins can add or
edit items on the website.
Primary actor: User/admin
Secondary actor: None
Precondition: The user should have a valid account.
Postcondition: The system displays relevant
homepage.
Main flow: 1. The user goes to the login
page.
The user inputs their
registered email and
password.
The user submits the login
form.
The system checks the user's
credentials.
If the credentials are correct,
the system grants the user
access.
The use case concludes.
VIEW PRODUCTS

Use Case Id: CL_UC_03
Use Case Name: View Product
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case allows users to view details of
products available on the platform. Both admins
and customers can browse products. Admins can
see options to edit product details, while
customers can see product descriptions, prices,
and availability.
Primary actor: User/admin
Secondary actor: None
Precondition: The user should be logged into their account.
Postcondition: The system displays the detailed
information of the selected product.
Main flow: 1. The user navigates to the product catalog
page.
The user selects a product they wish to
view.
The system retrieves the product details.
The system displays the product details,
including description, price, and
availability.
If the user is an admin, they also see options
to edit the product details.
The use case concludes.
PURCHASE PRODUCT

Use Case Id: CL_UC_04
Use Case Name: Purchase Product
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case allows customers to purchase
products from the platform. Customers select
products, proceed to checkout, and complete the
payment process.
Primary actor: User
Secondary actor: None
Precondition: The customer must be logged into their account
and have selected items to purchase.
Postcondition: The system confirms the purchase,
updates the order status, and processes the
payment.
Main flow: 1. The customer navigates to the product catalog and
selects the items they wish to purchase.
The customer adds the selected items to their
shopping cart.
The customer proceeds to the checkout page.
The system displays the order summary and
prompts the customer to enter payment
information.
The customer enters their payment details and
submits the payment.
The system processes the payment and
updates the order status.
The system confirms the purchase and
provides an order confirmation to the
customer.
The use case concludes.
MANAGE PRODUCTS

Use Case Id: CL_UC_05
Use Case Name: Manage Products
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case allows admins to manage products on
the platform. Admins can add new products, edit
existing product details, and remove products as
needed. This functionality ensures that the product
catalog remains up-to-date and accurate.
Primary actor: Admin
Secondary actor: None
Precondition: The admin must be logged into their account with
appropriate privileges.
Postcondition: The product catalog is updated with the
new, edited, or removed product details.
Main flow: 1. The admin navigates to the product
management page.
The admin selects an option to add, edit, or
remove a product.
To add a Product:
The admin enters the product details,
including name, description, price, and
availability.
The admin submits the new product
information.
The system updates the product catalog with
the new product.
To Edit a Product:
The admin selects the product to be edited
from the catalog.
The admin modifies the product details as
needed.
The admin submits the updated product
information.
The system updates the product catalog with
the edited product details.
To Remove a Product:
The admin selects the product to be
removed from the catalog.
The admin confirms the removal.
The system removes the product from the
catalog.
The system confirms the action (add, edit, or
remove) and updates the product catalog
accordingly.
The use case concludes.
BOOKING CONSULTATION AND SERVICES

Use Case Id: CL_UC_07
Use Case Name: Book Consultation and Services
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case allows users to book consultations
or services offered on the platform. Users can
select the type of consultation or service, choose
a suitable time slot, and confirm their booking.
The system manages and schedules the bookings.
Primary actor: User
Secondary actor: None
Precondition: The user must be logged into their account and
have access to available consultations or services.
Postcondition: The booking is confirmed, and the
system updates the schedule with the
new appointment.
Main flow: 1. The user navigates to the consultations or
services booking page.
The user selects the type of consultation or
service they wish to book.
The user chooses a preferred time slot from
the available options.
The user provides any additional required
information (e.g., details about the
consultation or service).
The user submits the booking request.
The system updates the schedule and sends
a confirmation to the user.
The use case concludes.
MANAGE BOOKING AND CONSULTAION

Use Case Id: CL_UC_08
Use Case Name: Manage Booking and Consultation
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: This use case allows admins or service providers to
manage bookings and consultations on the
platform. They can review, approve, modify, or
cancel bookings and consultations to ensure
smooth scheduling and service delivery.
Primary actor: Admin
Secondary actor: None
Precondition: The admin or service provider must be logged into
their account with appropriate privileges.
Postcondition: The booking and consultation details are
updated as per the actions taken
(approved,or canceled).
Main flow: i. The admin or service provider navigates to
the booking and consultation management
page.
ii. The admin or service provider reviews the
list of upcoming and pending bookings and
consultations
iii. The admin or service provider selects a
booking or consultation to manage.
To Approve a Booking/consultation:
The admin or service provider reviews
the booking details.
The admin or service provider approves
the booking or consultation.
The system updates the status of the
booking or consultation to "Accepted"
and sends a confirmation to the user.
To Cancel a Booking/Consultation:
The admin or service provider selects the
booking or consultation to be canceled.
The admin or service provider confirms
the cancellation.
The system updates the status to
"Canceled" and sends a cancellation
notice to the user.
iv. The system confirms the action
(accept, or cancel) and updates
the booking and consultation records
accordingly.
v. The use case concludes.
LOG OUT

Use Case Id: CL_UC_10
Use Case Name: Log Out
Created by: Derin & Sheethal
Date Created: 24 - 06 - 2024
Description: (^) This use case allows both users and
admins to log out of their accounts on the
platform. Logging out ensures that the
session is securely ended, and access is
restricted until the user or admin logs in
again.
Primary actor: User/Admin
Secondary actor: None
Precondition: The user or admin must be logged into
their account.
Postcondition: The user or admin must be logged into
their account.
Main flow: 1. The user or admin navigates to
the log out option.

The user or admin selects the
option to log out.
The system terminates the
session of the user or admin.
The system redirects the user or
admin to the login page.
The system confirms that the
user or admin has been
successfully logged out.
Use case ends.
4.3.2 USECASE DIAGRAM

4.3. 3 ACTIVITY DIAGRAMS

ADMIN SIDE

USER SIDE

4 .4 MODULE DETAILS
There are five main modules in this website:

➢ Login Module
➢ Sign-Up Module
➢ Product Discovery Module
➢ Consultation Booking Module
➢ Admin Dashboard Module
Login Module
The login module allows registered users and administrators to securely access the Celestia
website. It verifies user credentials (email and password) to grant access to personalized services
such as browsing designs, purchasing products, and managing consultations.

Sign Up Module
The sign-up module enables new users to create accounts on Celestia. Users provide personal
details including name, email, phone number, and password to register and access features like
saving favorite designs, tracking orders, and booking consultations.

Product Discovery Module
The product discovery module allows users to explore trendy design ideas and browse products
offered on Celestia. Users can view detailed product information, and add items to their
shopping cart for purchase.

Consultation Booking Module
The consultation booking module enables users to schedule virtual or in-person consultations
with design experts through Celestia and receive confirmation of their consultation bookings.

Admin Dashboard Module
The admin dashboard module provides administrators with tools to oversee consultation
schedules, update website content. Admins can ensure smooth platform operation and maintain
data integrity.

4.5 PERFORMANCE CONSIDERATIONS
Hardware Requirements

The system is designed to perform optimally with a minimum of 4GB RAM and is compatible
with Windows OS versions and higher.

4.6 SECURITY CONSIDERATIONS
Access Control

Authorized Access: Only users with valid usernames and passwords are allowed to
access the Celestia Interior and Exterior Design platform.
Login Security: The login process includes robust security measures to authenticate
users and prevent unauthorized access.
4.7 TABLE DESIGN
1.Table name: user

Sl.no Field Name Data Type Constraint Description
uid int(11) Primary Key,
Auto_increment,
Not null
User id
ufname varchar(20) Not null First name of the
user
ulname varchar(20) Not null Last name of the
user
uphone bigint(10) Unique Key,
Not null
Phone number of
the user
upass varchar(255) Not null Password of the
user
udob date Not null Date of birth of
the user
uemail varchar(100) Unique Key,
Not null
Email of the user
role Int(11) Default 2 Role specifying
user or admin
3.Table name: products

Sl.no Field Name Data Type Constraint Description
pid int(11) Primary key,
Auto_increment,
Not null
Product id
pname varchar(255) Not null Product name
price decimal Not null Product price
pimage varchar(255) Not null Product image
deleted tinyint(1) Yes, Default 0 Shows whether
deleted or not
4.Table name: consultations

Sl.no Field Name Data Type Constraint Description
id int(11) Primary key,
Auto_increment,
Not null
Consultation
id
name int(11) Foreign key,
Not null
User id from
user table
email varchar(255) Not null Type of
designer
4, phone varchar(20) Not null Phone no. of
user
consultation_type Enum(‘in_person’,’virtual’) Not null Type of
consulation
preferred_date date Not null Date of
consultation
Preferred_Time time Not null Time of
consultation
status enum Yes null Status of
consultation
Created_at timestamp Not null Time of
creation
5 .Table name: orders

Sl.no Field Name Data Type Constraint Description
Order_id int(11) Primary key,
Auto_increment,
Not null
Order id
User_id int(11) Foreign key,
Not null
User id from user
table
Product_id Int(11) Foreign Key,
Not null
Product id from
products table
quantity Int(11) Not null Quantity of
products
Order_Date datetime Yes null,
Current_Time
Date and time
ordered
status Varchar(50) Not null Status of order
6 .Table name: bank

Sl.no Field Name Data Type Constraint Description
bid int(11) Primary key,
Auto_increment,
Not null
Bank id
cvv int( 3 ) Not null CVV number of
card
bno Bigint(!1) Not null Bank Account
Number
bbal int(1 0 ) Not null Bank Account
balance
7 .Table name: accountholder

Sl.no Field Name Data Type Constraint Description
holder_id int(11) Primary key,
Auto_increment,
Not null
Bank Account
holder id
bid Int(11) Default null Bank id foreign
key from bank
table
bfname Varchar(255) Default null Accunt holder
first name
blname Varchar(255) Default null Account holder
last name
7 .Table name: bookings

Sl.no Field Name Data Type Constraint Description
Booking_id int(11) Primary key,
Auto_increment,
Not null
Booking id of
service
Userid int(11) Foreign key,
Not null
User id from
user table
Service_name Varchar(255) Not null Service name
Customer_name Varchar(255) Not null Name of
customer
Customer_email Varchar(255) Not null Email of
customer
Book_date date Not null Booking date
Created_at timestamp Not null, Default
current_timestamp()
Time of
booking
status enum Not null, Default
pending
Status of
booking
8 .Table name: cart

Sl.no Field Name Data Type Constraint Description
cid int(11) Primary key,
Auto_increment,
Not null
Cart id
pid Int(11) Not null Product id
foreign key from
products table
uid Int(11) Not null User id foreign
key from user
table
price Decimal(10,2) Not null Price of product
quantity Int(2) Not null, Default
1
Quantity of
product
9 .Table name: contact

Sl.no Field Name Data Type Constraint Description
id int(11) Primary key,
Auto_increment,
Not null
Contact id
name Varchar(255) Not null Name of the
customer
email Varchar(255) Not null Customer
email
message text Not null Contact
message
status Enum(‘unread’,’read’) Yes null, Default
unread
Status of
message
Date_Recieved timestamp Not null. Default
current_timestamp()
Date message
receieved
CODING
5.CODING
5.1 INTRODUCTION
Coding section is where the magic happens. All the planning and the designing done in the
previous sections come to life in this section. After this section can only the programmer enjoy
the result of his/her hard work when he/she runs the program for the first time.

5.2 SELECTION OF SOFTWARE
PHP
PHP, an acronym for Hypertext Preprocessor, is a versatile server-side scripting language that
falls under the broader category of software development. It is widely recognized for its pivotal
role in web development and boasts several essential features that make it a preferred choice for
building dynamic websites and web applications. Here are some of its key features:

Open Source
Database Integration
Embedded in HTML
Cross-Platform Compatibility
Security
MYSQL

MySQL is an open-source relational database system, widely used for web development task like
data storage, manipulation, and retrieval. It seamlessly integrates into web applications,
eliminating the need for complex setup. MySQL is embedded within web development
environments, making administrative tasks effortless. It operates as an SQL-based database,
storing data in text files on the device. Unlike systems like JDBC, MySQL simplifies data access
with its broad range of relational database features. Its features are

Zero configuration
Server less
Stable cross platform database file
Less memory
Self-contained
Transactional
5.3 CODING PHASE
The goal of the coding or programming phase is to translate the design of the system produced
during the design phase into code in a given programming language, which can be executed by a
computer and that performs the computation specified by the design. The coding phase affects
both testing and maintenance profoundly.

The coding phase does not affect the structure of the system; it has great impact on the internal
structure of modules, which affects the testability of the system. The goal of the coding phase is
to produce clear simple programs. The aim is not to reduce the coding effect, but to program in a
manner so that testing and maintenance costs are reduced.

Programs should not be constructed so that they are easy to write; they should be easy to read and
understand. Reading programs is a much more common activity than writing programs. Hence,
the goal of the coding phase is to produce simple programs that are clear to understand and modify.

5.3.1 CODING STANDARDS

The standard used in the development of the system is Microsoft Programming standards. It
includes naming conversations of variables, constants and objects, standardized formats for
labelling and commenting code, spacing, formatting and indenting.

Naming Conventions

The controls are prefixed to indicate their functions. The frames are prefixed with frm, textboxes
are prefixed with txt, command buttons with cmd, label boxes with lbl, list boxes with lst,
comboboxes with cmb, Date Time Pickers with DTP, Grid with grid and so on.

Labels and Comments

The functions of each control are labelled clearly in the GUI. The code also includes comments
so that other developers using the source code in future might understand the module functions
better.

TESTING
&
IMPLEMENTATION
6. TESTING
6.1 INTRODUCTION
Software testing is a critical element of software quality assurance and represents the ultimate
review of specifications design and coding. Testing presents an interesting anomaly for the
software. Testing is a quality measure process, which reveals the errors in the program. During
testing, the program is executed with a set of test cases and the output of the program for the test
cases is evaluated to determine if the program is performing as it is expected. Testing plays a very
critical role in determining the reliability and efficiency of the software and it is a very important
stage in software development.

6.2 TESTING
System testing is actually a series of different tests whose primary purpose is to fully exercise the
computer-based systems. Although each test has a different purpose, all work to verify that all
system elements have been properly integrated and perform allocated functions.

System testing is done in order to ensure that the system developed doesn’t fail at any point.
Before implementations, the system is tested with experimental data to ensure that it will meets
the specified requirements, special tests data are input for processing and results examined.

6.2.1 TEST PLAN

Preparation of test data

Taking various kinds of test data does the testing. Preparation of test data plays a vital role in the
system testing. After preparing, the test data the system under study is tested using that test data.
While testing the system by using test data errors are again uncovered and corrected by using
above testing steps and correction are also noted for future use. Two kinds of test data were
collected and used:

Using live test data

Live test is those that are actually extracted from organization files. After a system is
partially constructed, programmers or analyst often ask users to key in a set of data fom
their normal activities. Then, the system person uses this data as a way to partially test the
system. In order instance, programmers or analysts extract a set of live data from the files
and have entered themselves.
Using artificial test data

Artificial test data are created solely for test purpose, since they can be generated to test
all combinations of formats and values. In other words, the artificial data, which can
quickly be prepared by a data generating utility program in the information system
department, make possible the testing of all login and control paths through the program.
The most effective test program uses artificial test data generated by person other than
those who wrote the program.
In this project invalid data was entered to test whether the program would break or not.
These invalid data entries were randomly generated using random people. Many people
were given the software for testing the program. They use gibberish values to test if every
validation holds strong.
6.3 TESTING METHODS
Testing is generally done at two levels-testing of individual modules and testing of the entire
system. During system testing, the system is used experimentally to ensure that the software
does not fail that is, that it will run according to its specifications and the results examined. A
limited number of uses may be allowed to use the system so analysis can see whether they use it
in unforeseen ways. It is preferable to discover any surprise before the organization implements
the system and depends on it.

Testing is done throughout system development at various stages. It is always a good practice to
test the system at many different levels at various intervals, that is, sub systems, program
modules as work progresses and finally the system as a whole. During testing the major
activities are concentrated on the examination ad modification of the source code. Usually, this
testing is to be performed by the person other than the person who has really coded it. This is
done in order to ensure more complete and unbiased testing for making the software more
reliable.

There are two types of testing:

Black box testing
• White box testing
6.3.1 WHITE BOX TESTING

In white box testing, the internal logic of the modules is considered. Following levels of testing
are performed for the developed project:

6.3.1.1 Unit Testing

This involves the tests carried out on modules programs, which make up a system. This is also
called as a program testing. The units in a large system many modules at different levels are
needed. Unit testing focuses on the modules, independently of one another, to locate errors. The
program should be tested for correctness of logic applied and should detect errors in coding.
Before proceeding one must make sure that all the programs are working independently.

6.3.2 BLACK BOX TESTING

The concept of the black box is used to represent a system who’s inside workings are not available
for inspection. In a black box, the test item is treated as “black”, since its logic is unknown; all
that is known is what goes in and what comes out, or the input and output.

6.3.2.1 System Testing

The system testing is conducted on a complete, integrated system to evaluate the system’s
compliance with its specified requirement. It falls within scope of black box testing so no
knowledge of inner design or logic is needed. As a rule, system testing takes, as its input, all of
the integrated software components that have passed integration testing and also the software
system itself integrated with any applicable hardware system. The purpose of the integration
testing is to detect any inconsistencies between software units.

System testing is the stage of implementation, which is aimed at ensuring that the system works
accurately and efficiently before live operation commence. The logical design and the physical
design should be thoroughly and continually examined on paper ensure that they will work when
implemented.

6.3.2.2 Integration Testing

Integration testing is a systematic technique for constructing the program structure, while at the
same time conducting tests to uncover errors associated with interfacing. This is the program is
constructed and tested in small segments, which makes it easier to isolate and the following

common types of integration problems may be observed:
Version mistakes
Data integrity violations
Overlapping function
Resource problems especially in memory handling
Wrong type of parameter in function calls
6.3.2.3 Validation Testing

At the culmination of the integration testing, the software was completely assembled as a package,
interfacing errors have been uncovered and corrected and a final series of software validation
testing began.

In validation testing we test the system functions in a manner that can be reasonably expected by
customer, the system was tested against system requirement specification. Different unusual
inputs that the users may use were assumed and the outputs were verified for such unprecedented
inputs. Deviation or errors discovered at this step are corrected prior to the completion of this
project with the help of user by negotiating to establish a method for resolving deficiencies. Thus,
the proposed system under consideration has been tested by using validation testing and found to
be working satisfactorily. Validation checking is performed on the: -

Numeric Field: - The numeric field can contain only numbers from 0 to 9. An entry of any
character flashes an error message. The individual modules are checked for accuracy and what it
has to perform. Each module is subjected to test run along with sample data. The individually
tested module are integrated into a single system.

Character Field: - This field can only contain letters from A-Z and a-z. It is useful for name,
address fields and so on.

Check Null Fields: - Before entering values into the database or when updating, a validation is
done to check whether any NULL fields are present.

Email Fields: - A email only field with a limit of characters. All the necessary validation checks
were verified to see if invalid data ever enters into the database. Null values in fields were also
treated as invalid values.

Password Fields: - - A password only field with a limit of characters. All the necessary validation
checks were verified to see if invalid data ever enters into the database. Null values in fields were
also treated as invalid values.

6.3.3 OUTPUT TESTING

After performing validation test, the next phase is output test of the system, since no system could
be useful if it does not produce the desired output in the desired format. By consideration the
format of the report/output was generated or displayed and was tested. Here output format was
considered in one way: on the display screen.

6.3.4 USER ACCEPTANCE TESTING

User acceptance test of a system is the key factor for the success of the system. The system under
consideration was listed for user acceptance by keeping constant touch with the perspective user
of the system at the time of design, development and making changes whenever required. This
was done with the regards of the following points: -

Input screen design
Output design
Users from each of the 2 user types (Admin, user) were selected for user acceptance testing. The
Admin was given the software for testing with his username and password. The admin actions are
performed and see whether all details are entering into the database and working properly as
expected... The customers side is tested using a customer name and password by registering to the
system and see he can post property from the website.

6.4 IMPLEMENTATION
Implementation is the stage of the project when the theoretical design is turned into a working
system. The implementation stage is a system project in its own right. It includes careful planning,
investigation of current system and its constraints on implementation, design of methods to
achieve the changeover, training of the staff in the changeover procedure and evaluation of the
changeover method.

The first task in implementation is planning deciding on the methods and time scale to be adopted.
Once the planning has been completed the major effort is to ensure that the programs in the system
are working properly when the user has been trained.

The complete system involving both computer and user can be executed effectively. Thus, the
clear plans are prepared for the activities.

Successful implementation of the new system design is a critical phase in the system life cycle.
Implementation means the process of converting a new or a revised system design into an
operational one.

MAINTENANCE
&
ENHANCEMENT
7. MAINTENANCE AND ENHANCEMENT
7.1 MAINTENANCE
This software can be modified as need occurs. Maintenance includes all the activities after
installation of the software that is performed to keep the system operational. The process of
maintenance involves:

➢ Understanding the existing software

➢ Understand the effect of change

➢ Test for satisfaction

This software requires little to no maintenance. During the testing phase most maintenance duties
are performed. If a maintenance requirement occurs, it can be solved with ease

7.2 ENHANCEMENT
The Celestia website is built with a modular architecture, allowing for easy expansion and
additional functionalities. As the business grows and customer demands evolve, the platform
can seamlessly integrate new features to enhance the user experience.

Future enhancements to the Celestia website could include:

Virtual Design Consultation: Integrate virtual design consultations using video
conferencing tools, allowing clients to discuss their requirements with designers directly
from the platform.
3D Landscape Visualization: Offer customers 3D landscape modeling for garden and
interior designs, providing a detailed view of how the project will look before
implementation.
Booking Reminder Alerts: Implement SMS or email alerts for upcoming bookings,
along with calendar integrations for scheduled appointments, helping customers stay
informed.
Advanced Customer Profiles: Implement a profile system where users can track their
service history, view consultations, and manage upcoming bookings all from a
personalized dashboard.
AR Home Design: Implement Augmented Reality (AR) functionality that allows
customers to visualize interior design changes or outdoor projects directly on their
property using their mobile device camera.
These future developments will help enhance Celestia’s service offerings, attract new customers,
and provide a seamless, user-friendly experience that stays competitive as technology advances.

CONCLUSION
8. CONCLUSION
In today’s dynamic design and landscaping industry, technology
plays a pivotal role in shaping customer experiences and elevating service delivery. Traditional
methods of offering landscaping, pool building, and interior design services are being transformed
by innovative digital solutions. The Celestia website exemplifies this evolution, providing a
seamless bridge between customers and service providers. With a user-friendly, interactive
platform, Celestia harnesses modern web technologies to allow users to explore, book, and engage
with services from the comfort of their homes, eliminating the barriers of traditional consultations.

The primary goal of Celestia has always been to empower customers with easy access to high-
quality services in garden development, pool building, interior design, and more. Celestia
streamlines service inquiries, enhances transparency, and fosters trust within the service
community, setting new standards for customer interaction in the design and landscaping sectors.

As the industry continues to evolve, Celestia remains committed to further enhancements,
embracing new technologies, and meeting the growing needs of its customers. Looking ahead,
Celestia aims to continue revolutionizing service delivery by integrating technology and design
services to create even more seamless and impactful customer experiences.

BIBLIOGRAPHY
BIBLIOGRAPHY
WEBSITES

Chatgpt
http://www.wikipedia.org
http://www.tutorialpoint.com
http://www.pinterest.com
http://www.pexels.com
http://www.w3schools.com
APPENDIX
SCREENSHOTS

1.LOGIN PAGE

2.REGISTRATION PAGE

3.HOME PAGE

4.SERVICES PAGE

5.SERVICE BOOKING PAGE

6 .SHOP PAGE

6.CART PAGE

7.PAYMENT PAGE

8.CONTACT PAGE

ADMIN MODULE

1.DASHBOARD PAGE

2.MANAGE SHOP PAGE

3.MANAGE CONSULTATIONS PAGE

4.MANAGE MESSAGES PAGE

5.MANAGE SERVICES PAGE